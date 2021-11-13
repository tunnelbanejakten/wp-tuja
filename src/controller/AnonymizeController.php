<?php
namespace tuja\controller;

use tuja\data\model\Competition;
use tuja\data\model\Group;
use tuja\data\store\GroupDao;
use tuja\data\store\PersonDao;

class AnonymizeController {
	private $group_dao   = null;
	private $person_dao  = null;
	private $competition = null;

	function __construct( Competition $competition ) {
		$this->group_dao   = new GroupDao();
		$this->person_dao  = new PersonDao();
		$this->competition = $competition;
	}

	public function anonymize_participants_incl_contacts() {
		$this->anonymize(
			false,
			function ( Group $grp ) {
				return ! $grp->get_category()->get_rules()->is_crew();
			}
		);
	}

	public function anonymize_participants_excl_contacts() {
		$this->anonymize(
			true,
			function ( Group $grp ) {
				return ! $grp->get_category()->get_rules()->is_crew();
			}
		);
	}

	public function anonymize_all() {
		$this->anonymize(
			false,
			function ( Group $group ) {
				return true;
			}
		);
	}

	private function anonymize( bool $exclude_contacts, $group_filter ) {
		$all_groups = $this->group_dao->get_all_in_competition( $this->competition->id, true );

		$groups = array_filter( $all_groups, $group_filter );

		$group_ids = array_map(
			function ( Group $grp ) {
				return $grp->id;
			},
			$groups
		);

		$this->person_dao->anonymize( $group_ids, $exclude_contacts );
		$this->group_dao->anonymize( $group_ids );
	}
}
