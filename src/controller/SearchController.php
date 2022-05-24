<?php
namespace tuja\controller;

use tuja\data\model\Competition;
use tuja\data\model\Group;
use tuja\data\store\GroupDao;
use tuja\data\store\PersonDao;

class SearchController {
	private $group_dao   = null;
	private $person_dao  = null;
	private $competition = null;

	function __construct( Competition $competition ) {
		$this->group_dao   = new GroupDao();
		$this->person_dao  = new PersonDao();
		$this->competition = $competition;
	}

	public function search( string $query ) {
		$groups = $this->group_dao->search( $this->competition->id, $query );
		$people = $this->person_dao->search( $this->competition->id, $query );
		return array(
			'groups' => $groups,
			'people' => $people,
		);
	}
}
