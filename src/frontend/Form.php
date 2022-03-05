<?php

namespace tuja\frontend;

use DateTime;
use Exception;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\Response;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;
use tuja\Frontend;
use tuja\util\concurrency\LockValuesList;
use tuja\util\Strings;
use tuja\util\FormUtils;

class Form extends AbstractGroupView {
	private $form_key;
	private $form;

	private $question_dao;
	private $response_dao;
	private $form_dao;

	const RESPONSE_FIELD_NAME_PREFIX = 'tuja_formshortcode__response__';
	const ACTION_FIELD_NAME          = 'tuja_formshortcode__action';
	const OPTIMISTIC_LOCK_FIELD_NAME = 'tuja_formshortcode__optimistic_locks';
	const TRACKED_ANSWERS_FIELD_NAME = 'tuja_formshortcode__tracked_answers';
	const SCROLL_FIELD_NAME          = 'tuja_formshortcode__scroll';

	public function __construct( string $url, string $group_key, string $form_key ) {
		parent::__construct( $url, $group_key, 'Svara' );
		$this->form_key     = $form_key;
		$this->question_dao = new QuestionDao();
		$this->group_dao    = new GroupDao();
		$this->response_dao = new ResponseDao();
		$this->form_dao     = new FormDao();
	}

	public static function get_response_field( $question_id ) {
		return self::RESPONSE_FIELD_NAME_PREFIX . $question_id;
	}

	protected function get_form(): \tuja\data\model\Form {
		if ( ! $this->form ) {

			$form = $this->form_dao->get_by_key( $this->form_key );
			if ( $form == false ) {
				throw new Exception( Strings::get( 'form.not_found' ) );
			}

			$this->form = $form;
		}

		return $this->form;
	}

	function output() {
		Frontend::use_script( 'jquery' );
		Frontend::use_script( 'tuja-form.js' );
		Frontend::use_stylesheet( 'tuja-wp-form.css' );

		$form = $this->get_form_html();
		include 'views/form.php';
	}

	public function update_answers( $group_id ): array {
		$errors = array();

		$updates = $this->get_updated_answers();

		$updated_question_ids = array_keys( $updates );

		// Remove POSTed data associated with questions which the user did NOT updated. This ensured that, when the
		// form is rendered, we will always show the most recent data (in case other users have submitted new answers
		// to questions which the current user did not try to update). Without this "hack", the current user would see
		// old data (since the current user would see the old re-POSTed data instead of the new data in the database).
		$this->clear_unchanged_post_data( $updated_question_ids );

		if ( count( $updates ) > 0 ) {
			try {
				$current_locks_all     = LockValuesList::from_string( $_POST[ self::OPTIMISTIC_LOCK_FIELD_NAME ] );
				$current_locks_updates = $current_locks_all->subset( $updated_question_ids );

				$this->get_form_lock_validator()->check_optimistic_lock( $current_locks_updates );
			} catch ( FormLockException $e ) {
				$rejected_ids = $e->get_rejected_ids();

				// We do not want to present the previously inputted values in case we notice that another user has already answered the same questions.
				// Remove what the current user submitted, which will force the form to display the newest response instead of the current user's.
				foreach ( $rejected_ids as $response_question_id ) {
					// Remove POSTed data, thus ensuring that the user sees the newer answer in the database (when the form is rendered).
					unset( $_POST[ self::get_response_field( $response_question_id ) ] );

					// Remove answer from list of updates
					unset( $updates[ $response_question_id ] );

					$errors[ self::get_response_field( $response_question_id ) ] = Strings::get( 'form.optimistic_lock_error' );
				}
			}

			foreach ( $updates as $question_id => $user_answer_array ) {
				try {
					$new_response                   = new Response();
					$new_response->group_id         = $group_id;
					$new_response->form_question_id = $question_id;
					$new_response->submitted_answer = $user_answer_array;

					$affected_rows = $this->response_dao->create( $new_response );

					if ( $affected_rows === false ) {
						throw new Exception( Strings::get( 'form.unknown_error' ) );
					}
				} catch ( Exception $e ) {
					$errors[ self::get_response_field( $question_id ) ] = $e->getMessage();
				}
			}
		}

		return $errors;
	}

	private function is_submit_allowed(): bool {
		return $this->get_form()->is_submit_allowed();
	}

	private function is_form_opened(): bool {
		return $this->get_form()->is_opened();
	}

	public function get_optimistic_lock_value( array $displayed_question_ids ) {
		return $this->get_form_lock_validator()->get_optimistic_lock_value( $displayed_question_ids )->to_string();
	}

	public function get_form_html(): string {
		$html_sections = array();
		$group         = $this->get_group();

		$group_id = $group->id;

		if ( ! $this->is_form_opened() ) {
			return sprintf(
				'
				<div class="tuja-message-wrapper">
					<p class="tuja-message tuja-message-warning">%s</p>
				</div>',
				Strings::get( 'form.not_opened' )
			);
		}

		$is_read_only = ! $this->is_submit_allowed();

		$message_success = null;
		$message_error   = null;
		$errors          = array();
		$is_update       = isset( $_POST[ self::ACTION_FIELD_NAME ] ) && $_POST[ self::ACTION_FIELD_NAME ] === 'update';

		if ( $is_update ) {
			if ( ! $is_read_only ) {
				$errors = $this->update_answers( $group_id );
				if ( empty( $errors ) ) {
					$message_success = Strings::get( 'form.changes_saved' );
					$html_sections[] = sprintf( '<div class="tuja-message-wrapper"><p class="tuja-message tuja-message-success">%s</p></div>', $message_success );
				} else {
					$message_error   = Strings::get( 'form.could_not_save_changes' );
					$html_sections[] = sprintf( '<div class="tuja-message-wrapper"><p class="tuja-message tuja-message-error">%s</p></div>', trim( $message_error ) );
				}
			} else {
				$message_error   = Strings::get( 'form.read_only' );
				$html_sections[] = sprintf( '<div class="tuja-message-wrapper"><p class="tuja-message tuja-message-error">%s</p></div>', trim( $message_error ) );
			}
		}

		$responses              = $this->response_dao->get_latest_by_group( $group_id );
		$displayed_question_ids = array();
		$form_user_changes      = new FormUserChanges();
		$form_utils             = new FormUtils( $group );
		$form_view              = $form_utils->get_form_view( $this->get_form(), FormUtils::RETURN_DATABASE_QUESTION_OBJECT, true );

		foreach ( $form_view->question_groups as $question_group_view ) {
			$current_group = '<section class="tuja-question-group">';
			if ( $question_group_view['name'] ) {
				$current_group .= sprintf( '<h2 class="tuja-question-group-title">%s</h2>', $question_group_view['name'] );
			}

			foreach ( $question_group_view['questions'] as $question_view ) {
				$question   = $question_view['obj'];
				$field_name = self::get_response_field( $question->id );

				// We do not want to present the previously inputted values in case the user changed from one group to another.
				// The responses inputted for the previously selected group are not relevant anymore (they are, in fact, probably incorrect).
				// Keep the previous form values if the user clicked "Update responses", not otherwise.
				if ( ! $is_update ) {
					// Clear input field value from previous submission:
					unset( $_POST[ $field_name ] );
				}
				$answer_object = @$responses[ $question->id ]->submitted_answer ?: null;
				$form_user_changes->track_answer( $question, $question->get_answer_object( $field_name, $answer_object, $group ) );

				$html_field = $question->get_html(
					$field_name,
					$is_read_only,
					$answer_object,
					$group
				);

				$current_group .= sprintf(
					'<div class="tuja-question %s" data-id="%d">%s%s</div>',
					isset( $errors[ $field_name ] ) ? 'tuja-field-error' : '',
					$question->id,
					$html_field,
					isset( $errors[ $field_name ] ) ? sprintf( '<p class="tuja-message tuja-message-error">%s</p>', $errors[ $field_name ] ) : ''
				);

				$displayed_question_ids[] = $question->id;
			}

			$current_group  .= '</section>';
			$html_sections[] = $current_group;
		}

		if ( ! $is_read_only ) {
			$optimistic_lock_value = $this->get_optimistic_lock_value( $displayed_question_ids );

			$tracked_answers_value = $form_user_changes->get_tracked_answers_string();

			$html_sections[] = sprintf( '<input type="hidden" name="%s" value="%s">', self::TRACKED_ANSWERS_FIELD_NAME, $tracked_answers_value );
			$html_sections[] = sprintf( '<input type="hidden" name="%s" value="%s">', self::OPTIMISTIC_LOCK_FIELD_NAME, $optimistic_lock_value );

			$html_sections[] = sprintf(
				'
					<div class="tuja-buttons tuja-buttons-fixed-footer">
						<button type="submit" name="%s" value="update">%s</button>
						<span class="tuja-form-change-reminder">%s</span>
					</div>',
				self::ACTION_FIELD_NAME,
				Strings::get( 'form.save_changes' ),
				Strings::get( 'form.form_change_reminder' )
			);
		} else {
			$html_sections[] = sprintf(
				'<p class="tuja-message tuja-message-error">%s</p>',
				Strings::get( 'form.read_only' )
			);
		}

		$html_sections[] = sprintf( '<input type="hidden" name="%s" id="%s" value="%s">', self::SCROLL_FIELD_NAME, self::SCROLL_FIELD_NAME, @$_POST[ self::SCROLL_FIELD_NAME ] ?: '' );
		$html_sections[] = sprintf( '<input type="hidden" name="%s" value="%s">', 'group', $this->get_group()->random_id );

		return sprintf( '<form method="post" enctype="multipart/form-data" id="tuja-form">%s</form>', join( $html_sections ) );
	}

	private function get_form_lock_validator(): FormLockValidator {
		return new FormLockValidator( $this->response_dao, $this->get_group() );
	}

	private function get_updated_answers(): array {
		$questions = $this->question_dao->get_all_in_form( $this->get_form()->id );
		$responses = $this->response_dao->get_latest_by_group( $this->get_group()->id );

		$user_answers = array_combine(
			array_map(
				function ( AbstractQuestion $question ) {
					return $question->id;
				},
				$questions
			),
			array_map(
				function ( AbstractQuestion $question ) use ( $responses ) {
					$answer_object = @$responses[ $question->id ]->submitted_answer ?: null;

					return $question->get_answer_object( self::get_response_field( $question->id ), $answer_object, $this->get_group() );
				},
				$questions
			)
		);

		$updates = FormUserChanges::get_updated_answer_objects( $_POST[ self::TRACKED_ANSWERS_FIELD_NAME ], $user_answers );

		return $updates;
	}

	/**
	 * Removed POSTed data which has NOT been updated.
	 */
	private function clear_unchanged_post_data( array $changed_question_ids ) {
		$updated_question_field_names = array_map(
			function ( $question_id ) {
				return self::get_response_field( $question_id );
			},
			$changed_question_ids
		);

		foreach ( array_keys( $_POST ) as $field_name ) {
			$is_form_input_field        = substr( $field_name, 0, strlen( self::RESPONSE_FIELD_NAME_PREFIX ) ) === self::RESPONSE_FIELD_NAME_PREFIX;
			$is_not_updated_input_field = ! in_array( $field_name, $updated_question_field_names );
			if ( $is_form_input_field && $is_not_updated_input_field ) {
				unset( $_POST[ $field_name ] );
			}
		}
	}
}
