<?php


namespace tuja\util\fee;


use DateTime;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\PersonDao;

class DefaultGroupFeeCalculator implements GroupFeeCalculator {

	const FEE_ATTENDING_PARTICIPANT = 100;
	const FEE_ATTENDING_NONPARTICIPANT = 0;
	const FEE_CREW = 0;

	private $person_dao;

	public function __construct() {
		$this->person_dao = new PersonDao();
	}

	function calculate_fee( Group $group, DateTime $date ): int {
		$is_crew = $group->get_category()->get_rules()->is_crew();
		if ( $is_crew ) {
			return self::FEE_CREW;
		} else {
			$people = $this->person_dao->get_all_in_group( $group->id, false, $date );

			return array_sum( array_map( function ( Person $person ) {
				return $person->is_competing()
					? self::FEE_ATTENDING_PARTICIPANT
					: self::FEE_ATTENDING_NONPARTICIPANT;
			}, $people ) );
		}
	}

	function description(): string {
		return sprintf(
			'%d kr per tävlande och gratis för funktionärer och ledare',
			self::FEE_ATTENDING_PARTICIPANT );
	}
}