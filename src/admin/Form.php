<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\QuestionGroup;
use tuja\data\store\QuestionGroupDao;
use tuja\util\DateUtils;
use tuja\data\store\CompetitionDao;
use tuja\util\ReflectionUtils;
use tuja\util\QuestionNameGenerator;

class Form extends Forms {
	const FORM_FIELD_NAME_PREFIX    = 'tuja-question';
	const ACTION_NAME_DELETE_PREFIX = 'question_group_delete__';

	protected $question_group_dao;
	protected $form;

	public function __construct() {
		parent::__construct();
		$this->question_group_dao = new QuestionGroupDao();

		if ( isset( $_GET['tuja_form'] ) ) {
			$this->form = $this->form_dao->get( $_GET['tuja_form'] );
		}
		$this->assert_set( 'Could not find form', $this->form );
		$this->assert_same( 'Form needs to belong to competition', $this->competition->id, $this->form->competition_id );
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		$forms_current = null;
		$forms_links   = array();
		$forms         = $this->form_dao->get_all_in_competition( $this->competition->id );
		foreach ( $forms as $form ) {
			$active = $form->id === $this->form->id;
			if ( $active ) {
				$forms_current = $form->name;
			}
			$link          = add_query_arg(
				array(
					'tuja_view'           => 'Form',
					'tuja_competition'    => $this->competition->id,
					'tuja_form'           => $form->id,
					'tuja_question_group' => null,
				)
			);
			$forms_links[] = BreadcrumbsMenu::item( $form->name, $link, $active );
		}
		$menu->add(
			BreadcrumbsMenu::item( $forms_current ),
			...$forms_links,
		);

		return $menu;
	}

	public function handle_post() {
		global $wpdb;

		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] == 'form_update' ) {
			try {
				$this->form->submit_response_start = DateUtils::from_date_local_value( $_POST['tuja-submit-response-start'] );
				$this->form->submit_response_end   = DateUtils::from_date_local_value( $_POST['tuja-submit-response-end'] );
				$success                           = $this->form_dao->update( $this->form );
			} catch ( Exception $e ) {
				$success = false;
			}

			$success !== false ? AdminUtils::printSuccess( 'Uppdaterat!' ) : AdminUtils::printException( $e );
		} elseif ( $_POST['tuja_action'] == 'question_group_create' ) {
			$group_props          = new QuestionGroup();
			$group_props->text    = null;
			$group_props->form_id = $this->form->id;

			$success = $this->question_group_dao->create( $group_props );

			$success !== false ? AdminUtils::printSuccess( 'Grupp skapad!' ) : AdminUtils::printError( 'Kunde inte skapa grupp.' );
		}

		QuestionNameGenerator::update_competition_questions( $this->form->competition_id );
	}

	public function get_scripts(): array {
		return array(
			'admin-formgenerator.js',
			'admin-forms.js',
			'jsoneditor.min.js',
		);
	}

	private function get_preview_url() {
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
		$this->handle_post();

		$db_competition  = new CompetitionDao();
		$competition     = $db_competition->get( $this->form->competition_id );
		$question_groups = $this->question_group_dao->get_all_in_form( $this->form->id );
		$preview_url     = $this->get_preview_url();

		include( 'views/form.php' );
	}

}
