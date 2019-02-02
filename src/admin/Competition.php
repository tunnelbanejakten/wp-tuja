<?php

namespace tuja\admin;

use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\util\score\ScoreCalculator;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\CompetitionDao;

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
		$db_groups = new GroupDao();
		$db_form   = new FormDao();

		if ( $_POST['tuja_action'] == 'group_create' ) {
			$props                 = new Group();
			$props->name           = $_POST['tuja_group_name'];
			$props->category_id    = $_POST['tuja_group_type'];
			$props->competition_id = $this->competition->id;
			$db_groups->create( $props );
		} elseif ( $_POST['tuja_action'] == 'form_create' ) {
			$props                 = new Form();
			$props->name           = $_POST['tuja_form_name'];
			$props->competition_id = $this->competition->id;
			$db_form->create( $props );
		}
	}


	public function output() {
		$this->handle_post();

		$db_form   = new FormDao();
		$db_groups = new GroupDao();

		$forms  = $db_form->get_all_in_competition( $this->competition->id );
		$groups = $db_groups->get_all_in_competition( $this->competition->id );

		$competition = $this->competition;

		include( 'views/competition.php' );
	}
}