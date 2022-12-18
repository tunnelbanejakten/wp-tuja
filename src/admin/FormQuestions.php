<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\question\ImagesQuestion;
use tuja\data\model\question\NumberQuestion;
use tuja\data\model\question\OptionsQuestion;
use tuja\data\model\question\TextQuestion;
use tuja\data\store\FormDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\CompetitionDao;
use tuja\util\ReflectionUtils;
use tuja\util\QuestionNameGenerator;

class FormQuestions extends Form {
	const FORM_FIELD_NAME_PREFIX    = 'tuja-question';
	const ACTION_NAME_DELETE_PREFIX = 'question_delete__';
	const ACTION_NAME_CREATE_PREFIX = 'question_create__';

	const ACTION_NAME_CREATE_TEXT    = self::ACTION_NAME_CREATE_PREFIX . 'text';
	const ACTION_NAME_CREATE_NUMBER  = self::ACTION_NAME_CREATE_PREFIX . 'number';
	const ACTION_NAME_CREATE_IMAGES  = self::ACTION_NAME_CREATE_PREFIX . 'images';
	const ACTION_NAME_CREATE_CHOICES = self::ACTION_NAME_CREATE_PREFIX . 'choices';

	protected $question_dao;

	public function __construct() {
		parent::__construct();
		$this->question_dao = new QuestionDao();

		if ( isset( $_GET['tuja_question_group'] ) ) {
			$this->question_group = $this->question_group_dao->get( $_GET['tuja_question_group'] );
		}
		$this->assert_set( 'Could not find question group', $this->question_group );
		$this->assert_same( 'Question group needs to belong to form', $this->form->id, $this->question_group->form_id );
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		$question_group_current = null;
		$question_group_links   = array();
		$question_groups        = $this->question_group_dao->get_all_in_form( intval( $_GET['tuja_form'] ) );
		foreach ( $question_groups as $question_group ) {
			$active = $question_group->id === $this->question_group->id;
			if ( $active ) {
				$question_group_current = $question_group->text ?? $question_group->id;
			}
			$link                   = add_query_arg(
				array(
					'tuja_view'           => 'FormQuestions',
					'tuja_competition'    => $this->competition->id,
					'tuja_form'           => $this->form->id,
					'tuja_question_group' => $question_group->id,
				)
			);
			$question_group_links[] = BreadcrumbsMenu::item( $question_group->text ?? $question_group->id, $link, $active );
		}

		$menu->add(
			BreadcrumbsMenu::item( $question_group_current ),
			...$question_group_links,
		);

		return $menu;
	}

	public function handle_post() {
		global $wpdb;

		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] == 'questions_update' ) {
			$wpdb->show_errors();

			$questions = $this->question_dao->get_all_in_group( $this->question_group->id );

			$success = true;
			foreach ( $questions as $question ) {
				if ( isset( $_POST[ self::FORM_FIELD_NAME_PREFIX . '__' . $question->id ] ) ) {

					$question->set_properties_from_json_string( stripslashes( $_POST[ self::FORM_FIELD_NAME_PREFIX . '__' . $question->id ] ) );

					try {
						$affected_rows = $this->question_dao->update( $question );
						$success       = $success && $affected_rows !== false;
					} catch ( Exception $e ) {
						$success = false;
					}
				}
			}

			$success ? AdminUtils::printSuccess( 'Uppdaterat!' ) : AdminUtils::printError( 'Kunde inte uppdatera fråga.' );
		} elseif ( substr( $_POST['tuja_action'], 0, strlen( self::ACTION_NAME_CREATE_PREFIX ) ) == self::ACTION_NAME_CREATE_PREFIX ) {
			$success = false;

			try {
				switch ( $_POST['tuja_action'] ) {
					case self::ACTION_NAME_CREATE_CHOICES:
						$props = new OptionsQuestion(
							null,
							'Items to choose from',               // text.
							'A subtle hint or reminder.',         // text_hint.
							0,                                    // id.
							$this->question_group->id,            // question_group_id.
							0,                                    // sort_order.
							0,                                    // limit_time.
							null,                                 // text_preparation.
							10,                                   // score_max.
							OptionsQuestion::GRADING_TYPE_ONE_OF, // score_type.
							true,                                 // is_single_select.
							array( 'Alice', 'Bob' ),              // correct_answers.
							array( 'Alice', 'Bob', 'Trudy' ),     // possible_answers.
							false                                 // submit_on_change.
						);
						break;
					case self::ACTION_NAME_CREATE_IMAGES:
						$props = new ImagesQuestion(
							null,
							'Upload an image',                       // text.
							'A subtle hint or reminder.',            // text_hint.
							0,                                       // id.
							$this->question_group->id,               // question_group_id.
							0,                                       // sort_order.
							0,                                       // limit_time.
							null,                                    // text_preparation.
							10,                                      // score_max.
							ImagesQuestion::DEFAULT_FILE_COUNT_LIMIT // max_files_count.
						);
						break;
					case self::ACTION_NAME_CREATE_TEXT:
						$props = new TextQuestion(
							null,
							'What? Who? When?',                // text.
							'A subtle hint or reminder.',      // text_hint.
							0,                                 // id.
							$this->question_group->id,         // question_group_id.
							0,                                 // sort_order.
							0,                                 // limit_time.
							null,                              // text_preparation.
							10,                                // score_max.
							TextQuestion::GRADING_TYPE_ONE_OF, // score_type.
							true,                              // is_single_answer.
							array( 'Alice', 'Alicia' ),        // correct_answers.
							array()                            // incorrect_answers.
						);
						break;
					case self::ACTION_NAME_CREATE_NUMBER:
						$props = new NumberQuestion(
							null,
							'How few, many, heavy, light...?', // text.
							'A subtle hint or reminder.',      // text_hint.
							0,                                 // id.
							$this->question_group->id,         // question_group_id.
							0,                                 // sort_order.
							0,                                 // limit_time.
							null,                              // text_preparation.
							10,                                // score_max.
							42                                 // correct_answer.
						);
						break;
					default:
						throw new Exception( 'Unsupported action' );
						break;
				}

				$new_id  = $this->question_dao->create( $props );
				$success = $new_id !== false;
			} catch ( Exception $e ) {
				$success = false;
			}

			$success === true ? AdminUtils::printSuccess( 'Fråga skapad!' ) : AdminUtils::printError( 'Kunde inte skapa fråga.' );
		} elseif ( substr( $_POST['tuja_action'], 0, strlen( self::ACTION_NAME_DELETE_PREFIX ) ) == self::ACTION_NAME_DELETE_PREFIX ) {
			$question_id_to_delete = substr( $_POST['tuja_action'], strlen( self::ACTION_NAME_DELETE_PREFIX ) );
			$affected_rows         = $this->question_dao->delete( $question_id_to_delete );
			$success               = $affected_rows !== false && $affected_rows === 1;

			if ( $success ) {
				AdminUtils::printSuccess( 'Fråga borttagen!' );
			} else {
				AdminUtils::printError( 'Kunde inte ta bort fråga.' );
				if ( $error = $wpdb->last_error ) {
					AdminUtils::printError( $error );
				}
			}
		}

		QuestionNameGenerator::update_competition_questions( $this->form->competition_id );
	}

	private function get_preview_url() {
		$short_name = substr( FormQuestionsPreview::class, strrpos( FormQuestionsPreview::class, '\\' ) + 1 );
		return add_query_arg(
			array(
				'action'              => 'tuja_questions_preview',
				'tuja_question_group' => $this->question_group->id,
				'tuja_view'           => $short_name,
				'TB_iframe'           => 'true',
				'width'               => '400',
				'height'              => '800',
			),
			admin_url( 'admin.php' )
		);
	}

	public function get_scripts(): array {
		return array(
			'admin-formgenerator.js',
			'admin-forms.js',
			'jsoneditor.min.js',
		);
	}

	public function output() {
		$this->handle_post();

		$db_competition = new CompetitionDao();
		$competition    = $db_competition->get( $this->form->competition_id );
		$questions      = $this->question_dao->get_all_in_group( $this->question_group->id );
		$preview_url    = $this->get_preview_url();

		$back_url = add_query_arg(
			array(
				'tuja_competition' => $competition->id,
				'tuja_form'        => $this->question_group->form_id,
				'tuja_view'        => 'Form',
			)
		);

		include( 'views/form-questions.php' );
	}

}
