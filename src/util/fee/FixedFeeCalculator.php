<?php


namespace tuja\util\fee;


use DateTimeInterface;
use tuja\data\model\Group;
use tuja\data\store\PersonDao;
use tuja\util\Strings;

class FixedFeeCalculator implements GroupFeeCalculator {

	private $person_dao;

	private $fee = 0;

	public function __construct() {
		$this->person_dao = new PersonDao();
	}

	function calculate_fee( Group $group, DateTimeInterface $date ): int {
		if ( $group->is_crew ) {
			return 0;
		} else {
			return $this->fee ?: 0;
		}
	}

	function description(): string {
		return Strings::get( 'fixedfeecalculator.description', $this->fee );
	}

	function configure( $config ) {
		$this->fee = $config['fee'] ?: 0;
	}

	function get_config_json_schema() {
		return
			[
				"properties" => [
					"fee" => [
						"title"  => 'Avgift per lag',
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