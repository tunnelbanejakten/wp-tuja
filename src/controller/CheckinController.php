<?php
namespace tuja\controller;

use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\Competition;
use tuja\data\store\GroupDao;
use tuja\data\store\PersonDao;
use Exception;

class CheckinController {
	private $group_dao   = null;
	private $person_dao  = null;

	function __construct(  ) {
		$this->group_dao   = new GroupDao();
		$this->person_dao  = new PersonDao();
	}

	public function check_in( Group $group, array $person_ids ) {
		if ( Group::STATUS_AWAITING_CHECKIN !== $group->get_status() ) {
			throw new Exception( 'Group cannot be checked in at this point.' );
		}

		$group->set_status( Group::STATUS_CHECKEDIN );

		$group_update_result = $this->group_dao->update( $group );

		if ( false === $group_update_result ) {
			throw new Exception( 'Could not set status to Checked In.' );
		}

		$people               = $this->person_dao->get_all_in_group( $group->id );
		$people_update_result = array_reduce(
			$people,
			function ( bool $result, Person $person ) use ( $person_ids ) {
				if ( in_array( $person->id, $person_ids ) ) {
					$person->set_status( Person::STATUS_CHECKEDIN );
					$this_update_result = $this->person_dao->update( $person );
					return $result && (false !== $this_update_result);
				}
				return $result;
			},
			true
		);

		if ( false === $people_update_result ) {
			throw new Exception( 'Could not set status to Checked In for (at least) one person.' );
		}
	}
}
