<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\question\TextQuestion;
use tuja\data\store\FormDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\CompetitionDao;

class FormQuestions {
	const FORM_FIELD_NAME_PREFIX = 'tuja-question';
	const ACTION_NAME_DELETE_PREFIX = 'question_delete__';

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
			$updated_questions = array_combine(array_map(function ($q) {
				return $q->id;
			}, $questions), $questions);

			foreach ($form_values as $form_question) {
				$form_question = json_decode(stripslashes($form_question), true);
				$id = (int)$form_question['id'];
				if(!isset($updated_questions[$id])) {
					trigger_error('Invalid question id.', 'warning');
					continue;
				}

				foreach($form_question as $field_name => $field_value) {
					switch ($field_name) {
						case 'type':
							// TODO: Don't set $type
							$updated_questions[$id]->type = $field_value;
							break;
						case 'text':
							$updated_questions[$id]->text = $field_value;
							break;
						case 'text_hint':
							$updated_questions[$id]->text_hint = $field_value;
							break;
						case 'score_type':
							$updated_questions[$id]->score_type = !empty($field_value) ? $field_value : null;
							break;
						case 'score_max':
							$updated_questions[$id]->score_max = $field_value;
							break;
						case 'correct_answers':
							$updated_questions[$id]->correct_answers = array_map('trim', $field_value);
							break;
						case 'possible_answers':
							$updated_questions[$id]->possible_answers = array_map('trim', $field_value);
							break;
						case 'sort_order':
							$updated_questions[$id]->sort_order = $field_value;
							break;
					}
				}
			}
		
			$success = true;
			foreach ($updated_questions as $updated_question) {
				try {
					$affected_rows   = $this->db_question->update( $updated_question );
					$success = $success && $affected_rows !== false;
				} catch (Exception $e) {
					$success = false;
				}
			}

			$success ? AdminUtils::printSuccess('Uppdaterat!') : AdminUtils::printError('Kunde inte uppdatera fråga.');
		} elseif ($_POST['tuja_action'] == 'question_create') {
			$success = false;
			
			try {
				$props = new TextQuestion( '?', null, TextQuestion::VALIDATION_TEXT, true, $this->question_group->id );
				$props->set_config( [
					'correct_answers' => [ 'Alice' ]
				] );

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
