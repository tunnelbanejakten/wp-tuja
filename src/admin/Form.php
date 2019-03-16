<?php

namespace tuja\admin;

use Exception;
use tuja\Admin;
use tuja\data\model\Question;
use tuja\data\store\FormDao;
use tuja\data\store\QuestionDao;
use tuja\util\DateUtils;
use tuja\data\store\CompetitionDao;

class Form {
	const FORM_FIELD_NAME_PREFIX = 'tuja-question';
	const ACTION_NAME_DELETE_PREFIX = 'question_delete__';

	private $form;
	private $db_form;
	private $db_question;

	public function __construct() {
		$this->db_form     = new FormDao();
		$this->db_question = new QuestionDao();
		$this->form        = $this->db_form->get( $_GET['tuja_form'] );

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
			foreach ($form_values as $field_name => $field_value) {
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
		
			$overall_success = true;
			foreach ($updated_questions as $updated_question) {
				try {
					$affected_rows   = $this->db_question->update( $updated_question );
					$this_success    = $affected_rows !== false && $affected_rows === 1;
					$overall_success = ($overall_success and $this_success);
				} catch (Exception $e) {
					$overall_success = false;
				}
			}

			if(!$overall_success) AdminUtils::printError('Kunde inte uppdatera fråga.');
		} elseif ($_POST['tuja_action'] == 'form_update') {
			try {
				$this->form->submit_response_start = DateUtils::from_date_local_value( $_POST['tuja-submit-response-start'] );
				$this->form->submit_response_end   = DateUtils::from_date_local_value( $_POST['tuja-submit-response-end'] );
				$this->db_form->update( $this->form );
			} catch (Exception $e) {
				AdminUtils::printException($e);
			}
		} elseif ($_POST['tuja_action'] == 'question_create') {
			$props                   = new Question();
			$props->correct_answers  = array('Alice');
			$props->possible_answers = array('Alice', 'Bob');
			$props->form_id          = $this->form->id;
			$props->type             = 'text'; // TODO: Use constant.

			try {
				$affected_rows = $this->db_question->create( $props );
				$success       = $affected_rows !== false && $affected_rows === 1;
			} catch (Exception $e) {
				$success = false;
			}
			
			if(!$success) AdminUtils::printError('Kunde inte skapa fråga.');
		} elseif (substr($_POST['tuja_action'], 0, strlen(self::ACTION_NAME_DELETE_PREFIX)) == self::ACTION_NAME_DELETE_PREFIX) {
			$wpdb->show_errors(); // TODO: Show nicer error message if question cannot be deleted (e.g. in case someone has answered the question already)
		
			$question_to_delete = substr($_POST['tuja_action'], strlen(self::ACTION_NAME_DELETE_PREFIX));

			$affected_rows = $this->db_question->delete( $question_to_delete );
			$success       = $affected_rows !== false && $affected_rows === 1;
			
			if(!$success) AdminUtils::printError('Kunde inte ta bort fråga.');
		}
	}

	public function output() {
		$this->handle_post();
		
		$db_competition = new CompetitionDao();
		$db_question    = new QuestionDao();
		$competition    = $db_competition->get($this->form->competition_id);

		$competition_url = add_query_arg(array(
			'tuja_view' => 'Competition',
			'tuja_competition' => $competition->id
		));

		include('views/form.php');
	}

}
