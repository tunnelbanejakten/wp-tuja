<?php


namespace tuja\util\fee;


use DateTime;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\PersonDao;
use tuja\util\Strings;

class PersonTypeFeeCalculator implements GroupFeeCalculator {

	const LABELS = [
		Person::PERSON_TYPE_LEADER     => 'Avgift för lagledare',
		Person::PERSON_TYPE_REGULAR    => 'Avgift för vanlig tävlande',
		Person::PERSON_TYPE_SUPERVISOR => 'Avgift för vuxen som följer med',
		Person::PERSON_TYPE_ADMIN      => 'Avgift för extra kontaktperson'
	];

	private $person_dao;

	private $fees = [];

	public function __construct() {
		$this->person_dao = new PersonDao();
	}

	function calculate_fee( Group $group, DateTime $date ): int {
		$is_crew = $group->get_category()->get_rules()->is_crew();
		if ( $is_crew ) {
			return 0;
		} else {
			$people = $this->person_dao->get_all_in_group( $group->id, false, $date );

			return array_sum( array_map( function ( Person $person ) {
				return $this->fees[ $person->get_type() ] ?: 0;
			}, $people ) );
		}
	}

	function description(): string {
		return join( ', ', array_map( function ( $person_type ) {
			return Strings::get( 'persontypefeecalculator.' . $person_type . '.description', $this->fees[ $person_type ] );
		}, Person::PERSON_TYPES ) );
	}

	function configure( $config ) {
		$this->fees = array_combine(
			Person::PERSON_TYPES,
			array_map( function ( $person_type ) use ( $config ) {
				return $config[ 'fee_' . $person_type ] ?: 0;
			}, Person::PERSON_TYPES ) );
	}

	function get_config_json_schema() {
		return
			[
				"properties" => array_combine( array_map( function ( $person_type ) {
					return 'fee_' . $person_type;
				}, Person::PERSON_TYPES ), array_map( function ( $person_type ) {
					return [
						"title"  => self::LABELS[ $person_type ],
						"type"   => "integer",
						"format" => "number"
					];
				}, Person::PERSON_TYPES ) )
			];
	}

	function get_default_config() {
		return array_combine(
			array_map( function ( $person_type ) {
				return 'fee_' . $person_type;
			}, Person::PERSON_TYPES ),
			array_fill( 0, count( Person::PERSON_TYPES ), 0 ) );

	}

	function get_config() {
		return array_combine(
			array_map( function ( $person_type ) {
				return 'fee_' . $person_type;
			}, Person::PERSON_TYPES ),
			array_map( function ( $person_type ) {
				return @$this->fees[ $person_type ] ?: 0;
			}, Person::PERSON_TYPES ) );

	}
}