<?php

namespace tuja\frontend;


use DateTime;
use Exception;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\Response;
use tuja\data\store\FormDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\Frontend;
use tuja\util\concurrency\LockValuesList;
use tuja\util\Strings;

class Form extends AbstractGroupView {
	private $form_key;
	private $form;

	private $question_dao;
	private $response_dao;
	private $category_dao;
	private $question_group_dao;
	private $form_dao;

	const RESPONSE_FIELD_NAME_PREFIX = 'tuja_formshortcode__response__';
	const ACTION_FIELD_NAME = 'tuja_formshortcode__action';
	const OPTIMISTIC_LOCK_FIELD_NAME = 'tuja_formshortcode__optimistic_locks';
	const TRACKED_ANSWERS_FIELD_NAME = 'tuja_formshortcode__tracked_answers';
	const SCROLL_FIELD_NAME = 'tuja_formshortcode__scroll';

	public function __construct( string $url, string $group_key, string $form_key ) {
		parent::__construct( $url, $group_key, 'Svara' );
		$this->form_key           = $form_key;
		$this->question_dao       = new QuestionDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->group_dao          = new GroupDao();
		$this->response_dao       = new ResponseDao();
		$this->form_dao           = new FormDao();
		$this->category_dao       = new GroupCategoryDao();
	}

	protected function get_form(): \tuja\data\model\Form {
		if ( ! $this->form ) {

			$form = $this->form_dao->get_by_key( $this->form_key );
			if ( $form == false ) {
				throw new Exception( 'Oj, vi hittade inte formuläret' );
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
		include( 'views/form.php' );
	}

	public function update_answers( $group_id ): array {
		$errors = array();

		$updates = $this->get_updated_answers();

		if ( count( $updates ) > 0 ) {
			try {
				$current_locks_all     = LockValuesList::from_string( $_POST[ self::OPTIMISTIC_LOCK_FIELD_NAME ] );
				$current_locks_updates = $current_locks_all->subset( array_keys( $updates ) );

				$this->get_form_lock_validator()->check_optimistic_lock( $current_locks_updates );
			} catch ( FormLockException $e ) {
				$rejected_ids = $e->get_rejected_ids();

				// We do not want to present the previously inputted values in case we notice that another user has already answered the same questions.
				// Remove what the current user submitted, which will force the form to display the newest response instead of the current user's.
				foreach ( $rejected_ids as $response_question_id ) {
					unset( $_POST[ self::RESPONSE_FIELD_NAME_PREFIX . $response_question_id ] );
					unset( $updates[ $response_question_id ] );
					$errors[ self::RESPONSE_FIELD_NAME_PREFIX . $response_question_id ] = Strings::get('form.optimistic_lock_error');
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
						throw new Exception( Strings::get('form.unknown_error') );
					}
				} catch ( Exception $e ) {
					$errors[ self::RESPONSE_FIELD_NAME_PREFIX . $question_id ] = $e->getMessage();
				}
			}
		}

		return $errors;
	}

	private function is_submit_allowed(): bool {
		return $this->is_form_opened() && ! $this->is_form_closed();
	}

	private function is_form_opened(): bool {
		$form = $this->get_form();
		$now  = new DateTime();
		if ( $form->submit_response_start != null && $form->submit_response_start > $now ) {
			return false;
		}

		return true;
	}

	private function is_form_closed(): bool {
		$form = $this->get_form();
		$now  = new DateTime();
		if ( $form->submit_response_end != null && $now > $form->submit_response_end ) {
			return true;
		}

		return false;
	}

	public function get_form_html(): string {
		$html_sections = [];
		$group         = $this->get_group();

		$group_id = $group->id;

		if ( ! $this->is_form_opened() ) {
			return sprintf( '
				<div class="tuja-message-wrapper">
					<p class="tuja-message tuja-message-warning">%s</p>
				</div>',
				Strings::get( 'form.not_opened' ) );
		}

		$is_read_only = ! $this->is_submit_allowed();

		$message_success = null;
		$message_error   = null;
		$errors          = [];
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
		$question_groups        = $this->question_group_dao->get_all_in_form( $this->get_form()->id );
		$displayed_question_ids = [];
		$form_user_changes      = new FormUserChanges();

		foreach ( $question_groups as $question_group ) {
			$current_group = '<section class="tuja-question-group">';
			if ( $question_group->text ) {
				$current_group .= sprintf( '<h2 class="tuja-question-group-title">%s</h2>', $question_group->text );
			}

			$questions = $question_group->get_filtered_questions( $this->question_dao, $this->group_dao, $group );
			foreach ( $questions as $question ) {
				$field_name = self::RESPONSE_FIELD_NAME_PREFIX . $question->id;

				// We do not want to present the previously inputted values in case the user changed from one group to another.
				// The responses inputted for the previously selected group are not relevant anymore (they are, in fact, probably incorrect).
				// Keep the previous form values if the user clicked "Update responses", not otherwise.
				if ( ! $is_update ) {
					// Clear input field value from previous submission:
					unset( $_POST[ $field_name ] );
				}
				$answer_object = @$responses[ $question->id ]->submitted_answer ?: null;
				$form_user_changes->track_answer( $question, $question->get_answer_object( $field_name, $answer_object ) );

				$html_field = $question->get_html(
					$field_name,
					$is_read_only,
					$answer_object,
					$group );

				$current_group .= sprintf( '<div class="tuja-question %s" data-id="%d">%s%s</div>',
					isset( $errors[ $field_name ] ) ? 'tuja-field-error' : '',
					$question->id,
					$html_field,
					isset( $errors[ $field_name ] ) ? sprintf( '<p class="tuja-message tuja-message-error">%s</p>', $errors[ $field_name ] ) : ''
				);

				$displayed_question_ids[] = $question->id;
			}

			$current_group   .= '</section>';
			$html_sections[] = $current_group;
		}

		if ( ! $is_read_only ) {
			$optimistic_lock_value = $this->get_form_lock_validator()
			                              ->get_optimistic_lock_value( $displayed_question_ids )
			                              ->to_string();

			$tracked_answers_value = $form_user_changes->get_tracked_answers_string();

			$html_sections[] = sprintf( '<input type="hidden" name="%s" value="%s">', self::TRACKED_ANSWERS_FIELD_NAME, $tracked_answers_value );
			$html_sections[] = sprintf( '<input type="hidden" name="%s" value="%s">', self::OPTIMISTIC_LOCK_FIELD_NAME, $optimistic_lock_value );

			$html_sections[] = sprintf( '
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
				Strings::get( 'form.read_only' ) );
		}

		$html_sections[] = sprintf( '<input type="hidden" name="%s" id="%s" value="%s">', self::SCROLL_FIELD_NAME, self::SCROLL_FIELD_NAME, $_POST[ self::SCROLL_FIELD_NAME ] ?: '' );
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
			array_map( function ( AbstractQuestion $question ) {
				return $question->id;
			}, $questions ),
			array_map( function ( AbstractQuestion $question ) use ( $responses ) {
				$answer_object = @$responses[ $question->id ]->submitted_answer ?: null;

				return $question->get_answer_object( self::RESPONSE_FIELD_NAME_PREFIX . $question->id, $answer_object );
			}, $questions ) );

		$updates = FormUserChanges::get_updated_answer_objects( $_POST[ self::TRACKED_ANSWERS_FIELD_NAME ], $user_answers );

		return $updates;
	}
}