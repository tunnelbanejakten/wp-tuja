<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\QuestionGroup;
use tuja\data\store\FormDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\util\DateUtils;
use tuja\data\store\CompetitionDao;

class Form {
	const FORM_FIELD_NAME_PREFIX = 'tuja-question';
	const ACTION_NAME_DELETE_PREFIX = 'question_group_delete__';

	private $form;
	private $db_form;
	private $db_question;
	private $db_question_group;

	public function __construct() {
		$this->db_form           = new FormDao();
		$this->db_question       = new QuestionDao();
		$this->db_question_group = new QuestionGroupDao();
		$this->form              = $this->db_form->get( $_GET['tuja_form'] );

		if(!$this->form) {
			print 'Could not find form';
			return;
		}
	}


	public function handle_post() {
		global $wpdb;

		if(!isset($_POST['tuja_action'])) return;

		if($_POST['tuja_action'] == 'question_groups_update') {
			$wpdb->show_errors();
		
			$form_values = array_filter($_POST, function ($key) {
				return substr($key, 0, strlen(self::FORM_FIELD_NAME_PREFIX)) === self::FORM_FIELD_NAME_PREFIX;
			}, ARRAY_FILTER_USE_KEY);

			$question_groups = $this->db_question_group->get_all_in_form( $this->form->id );
			$updated_groups = array_combine(array_map(function ($q) {
				return $q->id;
			}, $question_groups), $question_groups);

			foreach ($form_values as $form_group) {
				$form_group = json_decode(stripslashes($form_group), true);
				$id = (int)$form_group['id'];
				if(!isset($updated_groups[$id])) {
					trigger_error('Invalid group id.', 'warning');
					continue;
				}

				foreach($form_group as $field_name => $field_value) {
					switch ($field_name) {
						case 'text':
							$updated_groups[$id]->text = $field_value;
							break;
						case 'sort_order':
							$updated_groups[$id]->sort_order = $field_value;
							break;
						case 'score_max':
							$updated_groups[$id]->score_max = is_numeric($field_value) ? floatval($field_value) : null;
							break;
					}
				}
			}
		
			$success = true;
			foreach ($updated_groups as $updated_group) {
				try {
					$affected_rows = $this->db_question_group->update( $updated_group );
					$success = $success && $affected_rows !== false;
				} catch (Exception $e) {
					$success = false;
				}
			}

			$success ? AdminUtils::printSuccess('Uppdaterat!') : AdminUtils::printError('Kunde inte uppdatera grupp.');
		} elseif ($_POST['tuja_action'] == 'form_update') {
			try {
				$this->form->submit_response_start = DateUtils::from_date_local_value( $_POST['tuja-submit-response-start'] );
				$this->form->submit_response_end   = DateUtils::from_date_local_value( $_POST['tuja-submit-response-end'] );
				$success = $this->db_form->update( $this->form );
			} catch (Exception $e) {
				$success = false;
			}

			$success !== false ? AdminUtils::printSuccess('Uppdaterat!') : AdminUtils::printException($e);
		} elseif ($_POST['tuja_action'] == 'question_group_create') {
			$group_props          = new QuestionGroup();
			$group_props->text    = null;
			$group_props->form_id = $this->form->id;

			$success = $this->db_question_group->create( $group_props );

			$success === 1 ? AdminUtils::printSuccess('Grupp skapad!') : AdminUtils::printError('Kunde inte skapa grupp.');
		} elseif (substr($_POST['tuja_action'], 0, strlen(self::ACTION_NAME_DELETE_PREFIX)) == self::ACTION_NAME_DELETE_PREFIX) {
			$question_group_id_to_delete = substr( $_POST['tuja_action'], strlen( self::ACTION_NAME_DELETE_PREFIX ) );
			$affected_rows = $this->db_question_group->delete( $question_group_id_to_delete );
			$success       = $affected_rows !== false && $affected_rows === 1;
			
			if($success) {
				AdminUtils::printSuccess('Grupp borttagen!');
			} else {
				AdminUtils::printError('Kunde inte ta bort grupp.');
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
		$question_groups = $this->db_question_group->get_all_in_form($this->form->id);

		include('views/form.php');
	}

}
