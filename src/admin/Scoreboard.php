<?php

namespace tuja\admin;

use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\data\store\GroupCategoryDao;
use tuja\util\GroupCategoryCalculator;
use tuja\util\score\ScoreCalculator;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\CompetitionDao;
use tuja\data\model\ValidationException;

class Scoreboard {

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
	}


	public function output() {
		$this->handle_post();

		$db_groups = new GroupDao();
		$db_group_categories = new GroupCategoryDao();

		$groups = $db_groups->get_all_in_competition( $this->competition->id );
		$group_categories = $db_group_categories->get_all_in_competition( $this->competition->id );

		$competition = $this->competition;

		$category_calculator = new GroupCategoryCalculator( $competition->id );

		include( 'views/scoreboard.php' );
	}
}