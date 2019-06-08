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

class FormQuestions {
	const FORM_FIELD_NAME_PREFIX = 'tuja-question';
	const ACTION_NAME_DELETE_PREFIX = 'question_delete__';
	const ACTION_NAME_CREATE_PREFIX = 'question_create__';

	const ACTION_NAME_CREATE_TEXT = self::ACTION_NAME_CREATE_PREFIX . 'text';
	const ACTION_NAME_CREATE_NUMBER = self::ACTION_NAME_CREATE_PREFIX . 'number';
	const ACTION_NAME_CREATE_IMAGES = self::ACTION_NAME_CREATE_PREFIX . 'images';
	const ACTION_NAME_CREATE_CHOICES = self::ACTION_NAME_CREATE_PREFIX . 'choices';

	private $form;
	private $db_form;
	private $db_question;
	private $db_question_group;

	public function __construct() {
		$this->db_form           = new FormDao();
		$this->db_question       = new QuestionDao();
		$this->db_question_group = new QuestionGroupDao();

		$this->question_group    = $this->db_question_group->get($_GET['tuja_question_group']);
		$this->form              = $this->db_form->get( $this->question_group->form_id );

		if(!$this->form) {
			print 'Could not find form';
			return;
		}
	}


	public function handle_post() {
		global $wpdb;

		if(!isset($_POST['tuja_action'])) return;

		if($_POST['tuja_action'] == 'questions_update') {
			$wpdb->show_errors();
		
			$form_values = array_filter($_POST, function ($key) {
				return substr($key, 0, strlen(self::FORM_FIELD_NAME_PREFIX)) === self::FORM_FIELD_NAME_PREFIX;
			}, ARRAY_FILTER_USE_KEY);

			$questions = $this->db_question->get_all_in_group($this->question_group->id);

			$success = true;
			foreach ( $questions as $question ) {
				$editable_properties = $question->get_editable_fields();
				if ( isset( $_POST[ self::FORM_FIELD_NAME_PREFIX . '__' . $question->id ] ) ) {
					$values = json_decode( stripslashes( $_POST[ self::FORM_FIELD_NAME_PREFIX . '__' . $question->id ] ), true );
					foreach ( $editable_properties as $prop_conf ) {
						$question->{$prop_conf['name']} = $values[ $prop_conf['name'] ];
					}

					try {
						$affected_rows = $this->db_question->update( $question );
						$success       = $success && $affected_rows !== false;
					} catch ( Exception $e ) {
						$success = false;
					}
				}
			}

			$success ? AdminUtils::printSuccess('Uppdaterat!') : AdminUtils::printError('Kunde inte uppdatera fråga.');
		} elseif ( substr( $_POST['tuja_action'], 0, strlen( self::ACTION_NAME_CREATE_PREFIX ) ) == self::ACTION_NAME_CREATE_PREFIX ) {
			$success = false;
			
			try {
				switch ( $_POST['tuja_action'] ) {
					case self::ACTION_NAME_CREATE_CHOICES:
						$props = new OptionsQuestion(
							'Items to choose from',
							'A subtle hint or reminder.',
							[ 'Alice', 'Bob', 'Trudy' ],
							true,
							false,
							0,
							$this->question_group->id,
							0,
							[ 'Alice', 'Bob' ],
							10,
							OptionsQuestion::GRADING_TYPE_ONE_OF );
						break;
					case self::ACTION_NAME_CREATE_IMAGES:
						$props = new ImagesQuestion(
							'Upload an image',
							'A subtle hint or reminder.',
							0,
							$this->question_group->id,
							0,
							10 );
						break;
					case self::ACTION_NAME_CREATE_TEXT:
						$props = new TextQuestion(
							'What? Who? When?',
							'A subtle hint or reminder.',
							true,
							$this->question_group->id,
							0,
							0,
							10,
							TextQuestion::GRADING_TYPE_ONE_OF,
							[ 'Alice', 'Alicia' ] );
						break;
					case self::ACTION_NAME_CREATE_NUMBER:
						$props = new NumberQuestion(
							'How few, many, heavy, light...?',
							'A subtle hint or reminder.',
							0,
							$this->question_group->id,
							0,
							10 );
						break;
					default:
						throw new Exception( 'Unsupported action' );
						break;
				}

				$success = $this->db_question->create( $props );
			} catch ( Exception $e ) {
				$success = false;
			}

			$success === 1 ? AdminUtils::printSuccess('Fråga skapad!') : AdminUtils::printError('Kunde inte skapa fråga.');
		} elseif (substr($_POST['tuja_action'], 0, strlen(self::ACTION_NAME_DELETE_PREFIX)) == self::ACTION_NAME_DELETE_PREFIX) {
			$question_id_to_delete = substr( $_POST['tuja_action'], strlen( self::ACTION_NAME_DELETE_PREFIX ) );
			$affected_rows = $this->db_question->delete( $question_id_to_delete );
			$success       = $affected_rows !== false && $affected_rows === 1;
			
			if($success) {
				AdminUtils::printSuccess('Fråga borttagen!');
			} else {
				AdminUtils::printError('Kunde inte ta bort fråga.');
				if($error = $wpdb->last_error) {
					AdminUtils::printError($error);
				}
			}
		}
	}

	public function output() {
		$this->handle_post();
		
		$db_competition = new CompetitionDao();
		$competition    = $db_competition->get($this->form->competition_id);
		$questions = $this->db_question->get_all_in_group($this->question_group->id);

		include('views/form-questions.php');
	}

}
