<?php

namespace tuja\admin;

use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\util\score\ScoreCalculator;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\CompetitionDao;
use tuja\data\model\ValidationException;

class Competition {

	private $competition;

	public function __construct() {
		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $_GET['tuja_competition'] );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}


	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] == 'form_create' ) {
			$props                 = new Form();
			$props->name           = $_POST['tuja_form_name'];
			$props->competition_id = $this->competition->id;
			try {
				$db_form = new FormDao();
				$db_form->create( $props );
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			}
		}
	}

	public function get_scripts(): array {
		return [
			'admin-formgenerator.js',
			'admin-forms.js',
			'jsoneditor.min.js'
		];
	}

	public function output() {
		$this->handle_post();

		$db_form   = new FormDao();

		$competition = $this->competition;

		$forms  = $db_form->get_all_in_competition( $competition->id );

		include( 'views/competition.php' );
	}
}