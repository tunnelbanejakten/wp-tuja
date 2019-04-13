<?php

namespace tuja\admin;

use Exception;
use tuja\Admin;
use tuja\data\model\Question;
use tuja\data\model\QuestionGroup;
use tuja\data\store\FormDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\util\DateUtils;
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

			$questions = $this->db_question->get_all_in_form( $this->form->id );
		
			$updated_questions = array_combine(array_map(function ($q) {
				return $q->id;
			}, $questions), $questions);
			foreach ($form_values as $form_question) {
				$form_question = json_decode(stripslashes($form_question), true);

				foreach($form_question as $field_name => $field_value) {
					list(, $id, $attr) = explode('__', $field_name);
					switch ($attr) {
						case 'type':
							$updated_questions[$id]->type = $field_value;
							break;
						case 'text':
							$updated_questions[$id]->text = $field_value;
							break;
						case 'text_hint':
							$updated_questions[$id]->text_hint = $field_value;
							break;
						case 'scoretype':
							$updated_questions[$id]->score_type = !empty($field_value) ? $field_value : null;
							break;
						case 'scoremax':
							$updated_questions[$id]->score_max = $field_value;
							break;
						case 'correct_answers':
							$updated_questions[$id]->correct_answers = array_map('trim', explode("\n", trim($field_value)));
							break;
						case 'possible_answers':
							$updated_questions[$id]->possible_answers = array_map('trim', explode("\n", trim($field_value)));
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
		} elseif ($_POST['tuja_action'] == 'form_update') {
			try {
				$this->form->submit_response_start = DateUtils::from_date_local_value( $_POST['tuja-submit-response-start'] );
				$this->form->submit_response_end   = DateUtils::from_date_local_value( $_POST['tuja-submit-response-end'] );
				$success = $this->db_form->update( $this->form );
			} catch (Exception $e) {
				$success = false;
			}

			$success !== false ? AdminUtils::printSuccess('Uppdaterat!') : AdminUtils::printException($e);
		} elseif ($_POST['tuja_action'] == 'question_create') {

			$success = false;

			$group_props          = new QuestionGroup();
			$group_props->text    = null;
			$group_props->form_id = $this->form->id;

			$question_group_id = $this->db_question_group->create( $group_props );

			error_log( '😱' . $question_group_id );

			if ( $question_group_id !== false ) {

				$props                    = new Question();
				$props->correct_answers   = array( 'Alice' );
				$props->possible_answers  = array( 'Alice', 'Bob' );
				$props->question_group_id = $question_group_id;
				$props->type              = 'text'; // TODO: Use constant.

				try {
					$affected_rows = $this->db_question->create( $props );
					$success       = $affected_rows !== false && $affected_rows === 1;
				} catch ( Exception $e ) {
					$success = false;
				}
			}

			$success ? AdminUtils::printSuccess('Fråga skapad!') : AdminUtils::printError('Kunde inte skapa fråga.');
		} elseif (substr($_POST['tuja_action'], 0, strlen(self::ACTION_NAME_DELETE_PREFIX)) == self::ACTION_NAME_DELETE_PREFIX) {
			$wpdb->show_errors(); // TODO: Show nicer error message if question cannot be deleted (e.g. in case someone has answered the question already)

			$question_group_id_to_delete = substr( $_POST['tuja_action'], strlen( self::ACTION_NAME_DELETE_PREFIX ) );

			// TODO: Delete question group instead of individual question (assume one-to-one relationship for now)
			$affected_rows = $this->db_question_group->delete( $question_group_id_to_delete );
			$success       = $affected_rows !== false && $affected_rows === 1;
			
			$success ? AdminUtils::printSuccess('Fråga sparad!') : AdminUtils::printError('Kunde inte ta bort fråga.');
		}
	}

	public function output() {
		$this->handle_post();
		
		$db_competition = new CompetitionDao();
		$competition    = $db_competition->get($this->form->competition_id);
		$question_groups = $this->db_question_group->get_all_in_form($this->form->id);

		include('views/form.php');
	}

}
