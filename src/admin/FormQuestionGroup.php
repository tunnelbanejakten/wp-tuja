<?php

namespace tuja\admin;

use Exception;
use tuja\data\store\CompetitionDao;
use tuja\data\store\QuestionDao;
use tuja\util\QuestionNameGenerator;
use tuja\util\RouterInterface;

class FormQuestionGroup extends Form implements RouterInterface {
	const FORM_FIELD_NAME_PREFIX    = 'tuja-question-group';
	const ACTION_NAME_DELETE_PREFIX = 'question_group_delete__';
	const ACTION_UPDATE = 'question_group_update';
	
	const ACTION_NAME_CREATE_PREFIX = 'question_create__';
	const ACTION_NAME_CREATE_TEXT    = self::ACTION_NAME_CREATE_PREFIX . 'text';
	const ACTION_NAME_CREATE_NUMBER  = self::ACTION_NAME_CREATE_PREFIX . 'number';
	const ACTION_NAME_CREATE_IMAGES  = self::ACTION_NAME_CREATE_PREFIX . 'images';
	const ACTION_NAME_CREATE_CHOICES = self::ACTION_NAME_CREATE_PREFIX . 'choices';

	protected $question_dao;
	protected $question_group_dao;
	protected $question_group;

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
					'tuja_view'           => 'FormQuestionGroup',
					'tuja_competition'    => $this->competition->id,
					'tuja_form'           => $this->form->id,
					'tuja_question_group' => $question_group->id,
					'tuja_question'       => null
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

		if ( strpos( $_POST['tuja_action'], self::ACTION_NAME_CREATE_PREFIX ) !== false ) {
			wp_redirect(add_query_arg([
				'tuja_view' 	=> 'FormQuestion',
				'tuja_question' => $_POST['tuja_action']
			]));
			exit;
		}

		if ( $_POST['tuja_action'] === self::ACTION_UPDATE ) {
			$wpdb->show_errors();
			$id = self::FORM_FIELD_NAME_PREFIX . '__' . $this->question_group->id;

			if ( isset( $_POST[ $id ] ) ) {
				if(!empty($_POST[ $id ])) {
					$this->question_group->set_properties_from_json_string( stripslashes( $_POST[ $id ] ) );
				} else {
					$this->question_group->set_properties_from_array( $_POST );
				}

				try {
					$affected_rows = $this->question_group_dao->update( $this->question_group );
					QuestionNameGenerator::update_competition_questions( $this->form->competition_id );
					AdminUtils::printSuccess( 'Uppdaterat!' );
				} catch ( Exception $e ) {
					AdminUtils::printError( 'Kunde inte uppdatera grupp.' );
				}
			}
		}

		elseif ( strpos( $_POST['tuja_action'], self::ACTION_NAME_DELETE_PREFIX ) !== false ) {
			$question_group_id_to_delete = substr( $_POST['tuja_action'], strlen( self::ACTION_NAME_DELETE_PREFIX ) );
			$affected_rows               = $this->question_group_dao->delete( $question_group_id_to_delete );
			$success                     = $affected_rows !== false && $affected_rows === 1;

			if ( $success ) {
				QuestionNameGenerator::update_competition_questions( $this->form->competition_id );

				wp_redirect(remove_query_arg('tuja_question'));
				exit;
			} else {
				AdminUtils::printError( 'Kunde inte ta bort grupp.' );
				if ( $error = $wpdb->last_error ) {
					AdminUtils::printError( $error );
				}
			}
		}
	}

	public function get_scripts(): array {
		return array(
			'admin-formgenerator.js',
			'admin-forms.js',
			'jsoneditor.min.js',
		);
	}

	protected function get_preview_url() {
		$short_name = substr( FormQuestionsPreview::class, strrpos( FormQuestionsPreview::class, '\\' ) + 1 );
		return add_query_arg(
			array(
				'action'    => 'tuja_questions_preview',
				'tuja_form' => $this->form->id,
				'tuja_view' => $short_name,
				'TB_iframe' => 'true',
				'width'     => '400',
				'height'    => '800',
			),
			admin_url( 'admin.php' )
		);
	}

	public function output() {
		$db_competition = new CompetitionDao();
		$competition    = $db_competition->get( $this->form->competition_id );
		$preview_url    = $this->get_preview_url();

		$db_questions = new QuestionDao();
		$questions = $db_questions->get_all_in_group( $this->question_group->id );

		// Keeping this for backwards compatability
		$field_name = self::FORM_FIELD_NAME_PREFIX . '__' . $this->question_group->id;

		include( 'views/form-question-group.php' );
	}

}
