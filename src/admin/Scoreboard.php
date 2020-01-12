<?php

namespace tuja\admin;

use tuja\data\model\Group;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;

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

		$groups = array_values( array_filter( $db_groups->get_all_in_competition( $this->competition->id ), function ( Group $group ) {
			return $group->get_status() !== Group::STATUS_DELETED;
		} ) );
		$group_categories = $db_group_categories->get_all_in_competition( $this->competition->id );

		$competition = $this->competition;

		include( 'views/scoreboard.php' );
	}
}