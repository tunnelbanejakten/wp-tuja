<?php

namespace tuja\admin;

use tuja\data\model\Form;
use tuja\data\store\FormDao;
use tuja\data\model\ValidationException;
use tuja\util\QuestionNameGenerator;

class Forms extends Competition {

	protected $form;
	protected $form_dao;

	public function __construct() {
		parent::__construct();
		$this->form_dao = new FormDao();
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( 'form_create' === $_POST['tuja_action'] ) {
			$props                 = new Form();
			$props->name           = $_POST['tuja_form_name'];
			$props->competition_id = $this->competition->id;
			try {
				$new_form_id = $this->form_dao->create( $props );

				QuestionNameGenerator::update_competition_questions( $this->competition->id );

				$new_form_key = $this->form_dao->get( $new_form_id )->random_id;

				AdminUtils::printSuccess(
					sprintf(
						'<span
							id="tuja_new_form_message"
							data-form-id="%s"
							data-form-random-id="%s"
							>Nytt formulär har skapats</span>',
						$new_form_id,
						$new_form_key,
					)
				);
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
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

	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		$forms = $this->form_dao->get_all_in_competition( $competition->id );

		include( 'views/forms.php' );
	}
}
