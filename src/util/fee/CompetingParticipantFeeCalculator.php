<?php


namespace tuja\util\fee;


use DateTimeInterface;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\PersonDao;
use tuja\util\Strings;

class CompetingParticipantFeeCalculator implements GroupFeeCalculator {

	private $person_dao;

	private $fee = 0;

	public function __construct() {
		$this->person_dao = new PersonDao();
	}

	function calculate_fee( Group $group, DateTimeInterface $date ): int {
		$is_crew = $group->get_category()->get_rules()->is_crew();
		if ( $is_crew ) {
			return 0;
		} else {
			$people = $this->person_dao->get_all_in_group( $group->id, false, $date );

			return array_sum( array_map( function ( Person $person ) {
				return $person->is_competing() ? $this->fee : 0;
			}, $people ) );
		}
	}

	function description(): string {
		return Strings::get( 'competingparticipantfeecalculator.description', $this->fee );
	}

	function configure( $config ) {
		$this->fee = $config['fee'] ?: 0;
	}

	function get_config_json_schema() {
		return
			[
				"properties" => [
					"fee" => [
						"title"  => 'Avgift per tävlande',
						"type"   => "integer",
						"format" => "number"
					]
				]
			];
	}

	function get_default_config() {
		return [
			"fee" => 0
		];
	}

	function get_config() {
		return [
			"fee" => $this->fee
		];
	}
}