<?php

namespace tuja\frontend;


use DateTime;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\Response;
use tuja\data\model\ValidationException;
use tuja\data\store\FormDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\view\FieldChoices;
use tuja\view\FieldEmail;
use tuja\view\FieldPhone;
use tuja\view\FieldPno;
use tuja\view\FieldText;

class Form extends AbstractGroupView {
	private $form_id;

	private $question_dao;
	private $response_dao;
	private $category_dao;
	private $question_group_dao;
	private $form_dao;
	private $is_crew_override;

	const RESPONSE_FIELD_NAME_PREFIX = 'tuja_formshortcode__response__';
	const ACTION_FIELD_NAME = 'tuja_formshortcode__action';
	const OPTIMISTIC_LOCK_FIELD_NAME = 'tuja_formshortcode__optimistic_lock';
	const TEAMS_DROPDOWN_NAME = 'tuja_formshortcode__group';

	public function __construct( string $url, string $group_key, int $form_id ) {
		parent::__construct( $url, $group_key, 'Svara' );
		$this->form_id            = $form_id;
		$this->question_dao       = new QuestionDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->group_dao          = new GroupDao();
		$this->response_dao       = new ResponseDao();
		$this->form_dao           = new FormDao();
		$this->category_dao       = new GroupCategoryDao();
	}

	function output() {
		$form = $this->get_form_html();
		include( 'views/form.php' );
	}

	/**
	 * Returns the responses POSTed by the users which are different from the responses
	 * stored in the database.
	 */
	public function get_new_answers( $group_id ): array {
		$questions = $this->question_dao->get_all_in_form( $this->form_id );
		$responses = $this->response_dao->get_latest_by_group( $group_id );

		$updates = [];
		foreach ( $questions as $question ) {
			$user_answer = $question->get_answer_object( self::RESPONSE_FIELD_NAME_PREFIX . $question->id );

			if ( isset( $user_answer ) ) {
				if ( ! isset( $responses[ $question->id ] ) || $user_answer != $responses[ $question->id ]->submitted_answer ) {
					$updates[ $question->id ] = $user_answer;
				}
			}
		}

		return $updates;
	}

	public function update_answers( $group_id ): array {
		$errors          = array();
		$overall_success = true;

		if ( ! $this->is_submit_allowed() ) {
			return array( self::RESPONSE_FIELD_NAME_PREFIX => 'Svar får inte skickas in nu.' );
		}

		$updates = $this->get_new_answers( $group_id );

		if ( count( $updates ) > 0 ) {
			try {
				$this->check_optimistic_lock( $group_id, array_keys( $updates ) );
			} catch ( Exception $e ) {
				// We do not want to present the previously inputted values in case we notice that another user has already answered the same questions.
				// Remove what the current user submitted, which will force the form to display the newest response instead of the current user's.
				foreach ( array_keys( $updates ) as $response_question_id ) {
					unset( $_POST[ self::RESPONSE_FIELD_NAME_PREFIX . $response_question_id ] );
				}

				return array( self::RESPONSE_FIELD_NAME_PREFIX => $e->getMessage() );
			}
		}

		foreach ( $updates as $question_id => $user_answer_array ) {
			try {
				$new_response                   = new Response();
				$new_response->group_id         = $group_id;
				$new_response->form_question_id = $question_id;
				$new_response->submitted_answer = $user_answer_array;

				$affected_rows = $this->response_dao->create( $new_response );

				$this_success    = $affected_rows !== false;
				$overall_success = ( $overall_success and $this_success );
			} catch ( Exception $e ) {
				$overall_success                                           = false;
				$errors[ self::RESPONSE_FIELD_NAME_PREFIX . $question_id ] = $e->getMessage();
			}
		}

		return $errors;
	}

	private function is_submit_allowed(): bool {
		return $this->is_form_opened() && ! $this->is_form_closed();
	}

	private function is_form_opened(): bool {
		$form = $this->form_dao->get( $this->form_id );
		$now  = new DateTime();
		if ( $form->submit_response_start != null && $form->submit_response_start > $now ) {
			return false;
		}

		return true;
	}

	private function is_form_closed(): bool {
		$form = $this->form_dao->get( $this->form_id );
		$now  = new DateTime();
		if ( $form->submit_response_end != null && $now > $form->submit_response_end ) {
			return true;
		}

		return false;
	}

	public function get_form_html(): string {
		$html_sections = [];
		$group         = $this->get_group();

		$group_category              = $group->get_derived_group_category();
		$crew_user_must_select_group = $this->is_crew_override;
		$user_is_crew                = isset( $group_category ) && $group_category->get_rule_set()->is_crew();
		if ( $user_is_crew && $crew_user_must_select_group ) {
			$participant_groups = $this->get_participant_groups();

			$html_sections[] = sprintf( '<p>%s</p>', $this->get_groups_dropdown( $participant_groups ) );

			$selected_participant_group = $this->get_selected_group( $participant_groups );

			$target_group = $selected_participant_group;
		} else {
			$target_group = $group;
		}
		$target_group_id = $group->id;

		if ( ! $this->is_form_opened() ) {
			return sprintf( '<p class="tuja-message tuja-message-warning">%s</p>', 'Formuläret kan inte visas just nu.' );
		}

		$is_read_only = ! $this->is_submit_allowed();

		if ( $target_group_id ) {
			$message_success = null;
			$message_error   = null;
			$errors          = array();
			$is_update       = isset( $_POST[ self::ACTION_FIELD_NAME ] ) && $_POST[ self::ACTION_FIELD_NAME ] === 'update';

			if ( $is_update ) {
				$errors = $this->update_answers( $target_group_id );
				if ( empty( $errors ) ) {
					$message_success = 'Era svar har sparats.';
					$html_sections[] = sprintf( '<p class="tuja-message tuja-message-success">%s</p>', $message_success );
				} else {
					$message_error = 'Oj, det gick inte att spara era svar. ';
					if ( isset( $errors[ self::RESPONSE_FIELD_NAME_PREFIX ] ) ) {
						$message_error .= $errors[ self::RESPONSE_FIELD_NAME_PREFIX ];
					}
					$html_sections[] = sprintf( '<p class="tuja-message tuja-message-error">%s</p>', trim( $message_error ) );
				}
			}

			$responses       = $this->response_dao->get_latest_by_group( $target_group_id );
			$question_groups = $this->question_group_dao->get_all_in_form( $this->form_id );

			foreach ( $question_groups as $question_group ) {
				$current_group = '<section class="tuja-question-group">';
				if ( $question_group->text ) {
					$current_group .= sprintf( '<h2 class="tuja-question-group-title">%s</h2>', $question_group->text );
				}

				$questions = $this->question_dao->get_all_in_group( $question_group->id );
				foreach ( $questions as $question ) {
					$field_name = self::RESPONSE_FIELD_NAME_PREFIX . $question->id;

					// We do not want to present the previously inputted values in case the user changed from one group to another.
					// The responses inputted for the previously selected group are not relevant anymore (they are, in fact, probably incorrect).
					// Keep the previous form values if the user clicked "Update responses", not otherwise.
					if ( ! $is_update ) {
						// Clear input field value from previous submission:
						unset( $_POST[ $field_name ] );
					}
					$html_field = $question->get_html(
						$field_name,
						$is_read_only,
						isset( $responses[ $question->id ] ) ? $responses[ $question->id ]->submitted_answer : null,
						$target_group );

					$current_group .= sprintf( '<div class="tuja-question %s" data-id="%d">%s%s</div>',
						isset( $errors[ $field_name ] ) ? 'tuja-field-error' : '',
						$question->id,
						$html_field,
						isset( $errors[ $field_name ] ) ? sprintf( '<p class="tuja-message tuja-message-error">%s</p>', $errors[ $field_name ] ) : ''
					);
				}

				$current_group   .= '</section>';
				$html_sections[] = $current_group;
			}

			if ( ! $is_read_only ) {
				$questions             = $this->question_dao->get_all_in_form( $this->form_id );
				$question_ids          = array_map( function ( $question ) {
					return $question->id;
				}, $questions );
				$optimistic_lock_value = $this->get_optimistic_lock_value( $target_group_id, (array) $question_ids );

				$html_sections[] = sprintf( '<input type="hidden" name="%s" value="%s">', self::OPTIMISTIC_LOCK_FIELD_NAME, $optimistic_lock_value );
				$html_sections[] = sprintf( '<input type="hidden" name="%s" value="%s">', 'group', $this->get_group()->random_id );

				$html_sections[] = sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="update">Uppdatera svar</button></div>', self::ACTION_FIELD_NAME );
			} else {
				$html_sections[] = sprintf( '<p class="tuja-message tuja-message-error">%s</p>',
					'Svar får inte skickas in nu.' );
			}

		}

		return sprintf( '<form method="post" enctype="multipart/form-data">%s</form>', join( $html_sections ) );
	}

	private function get_groups_dropdown( $participant_groups ): string {
		$field = new FieldChoices(
			'Vilket lag vill du rapportera för?',
			'Byt inte lag om du har osparade ändringar.',
			true,
			array_merge(
				array( '' => 'Välj lag' ),
				array_map( function ( $option ) {
					return $option->name;
				}, $participant_groups ) ),
			false,
			true );

		return $field->render( self::TEAMS_DROPDOWN_NAME, null );
	}

	private function get_participant_groups(): array {
		$form           = $this->form_dao->get( $this->form_id );
		$competition_id = $form->competition_id;

		$categories             = $this->category_dao->get_all_in_competition( $competition_id );
		$participant_categories = array_filter( $categories, function ( $category ) {
			return ! $category->get_rule_set()->is_crew();
		} );
		$ids                    = array_map( function ( $category ) {
			return $category->id;
		}, $participant_categories );

		$competition_groups = $this->group_dao->get_all_in_competition( $competition_id );
		$participant_groups = array_filter( $competition_groups, function ( Group $group ) use ( $ids ) {
			$group_category = $group->get_derived_group_category();

			return isset( $group_category ) && in_array( $group_category->id, $ids );
		} );

		return $participant_groups;
	}

	private function get_selected_group( $participant_groups ) {
		$selected_group_name = $_POST[ self::TEAMS_DROPDOWN_NAME ];
		$selected_group      = array_values( array_filter( $participant_groups, function ( $group ) use ( $selected_group_name ) {
			return strcmp( $group->name, $selected_group_name ) == 0;
		} ) )[0];

		return $selected_group;
	}

	private function get_optimistic_lock_value( $group_id, array $response_question_ids ) {

		$responses            = $this->response_dao->get_latest_by_group( $group_id );
		$response_by_question = array_combine( array_map( function ( $resp ) {
			return $resp->form_question_id;
		}, $responses ), $responses );

		$current_optimistic_lock_value = array_reduce( $response_question_ids, function ( $carry, $response_question_id ) use ( $response_by_question ) {
			$response = isset( $response_by_question[ $response_question_id ] ) ? $response_by_question[ $response_question_id ] : null;
			if ( $response && ! is_null( $response->created ) ) {
				return max( $carry, $response->created->getTimestamp() );
			}

			return $carry;
		}, 0 );

		return $current_optimistic_lock_value;
	}

	private function check_optimistic_lock( $group_id, array $response_question_ids ) {
		$current_optimistic_lock_value = $this->get_optimistic_lock_value( $group_id, $response_question_ids );

		if ( $current_optimistic_lock_value > $_POST[ self::OPTIMISTIC_LOCK_FIELD_NAME ] ) {
			throw new Exception( '' .
			                     'Medan du fyllde i formuläret hann någon annan i ditt lag skicka in andra svar på några av frågorna. ' .
			                     'En del av dina svar har därför inte sparats och istället ser du vad den andra personen svarade.' );
		}
	}
}