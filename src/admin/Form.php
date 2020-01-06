<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\QuestionGroup;
use tuja\data\store\FormDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\util\DateUtils;
use tuja\data\store\CompetitionDao;
use tuja\util\ReflectionUtils;

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
		
			$question_groups = $this->db_question_group->get_all_in_form( $this->form->id );

			$success = true;
			foreach ( $question_groups as $question_group ) {
				if ( isset( $_POST[ self::FORM_FIELD_NAME_PREFIX . '__' . $question_group->id ] ) ) {

					$question_group->set_properties_from_json_string( stripslashes( $_POST[ self::FORM_FIELD_NAME_PREFIX . '__' . $question_group->id ] ) );

					try {
						$affected_rows = $this->db_question_group->update( $question_group );
						$success       = $success && $affected_rows !== false;
					} catch ( Exception $e ) {
						$success = false;
					}
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

			$success !== false ? AdminUtils::printSuccess('Grupp skapad!') : AdminUtils::printError('Kunde inte skapa grupp.');
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
