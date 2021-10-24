<?php

namespace tuja\controller;

use tuja\data\model\Competition;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupDao;

class DeleteCompetitionController {
	function __construct() {
		$this->competition_dao      = new CompetitionDao();
		$this->group_dao            = new GroupDao();
	}

	public function delete( Competition $competition ) {
        $groups = $this->group_dao->get_all_in_competition( $competition->id, true, null );
		foreach ( $groups as $group ) {
			$this->group_dao->delete( $group->id );
		}
        $this->competition_dao->delete( $competition->id );
	}
}
