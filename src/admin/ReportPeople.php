<?php

namespace tuja\admin;

use tuja\data\model\Person;
use tuja\data\store\PersonDao;

class ReportPeople extends AbstractReport {
	private $person_dao;

	public function __construct() {
		parent::__construct();
		$this->person_dao = new PersonDao();
	}

	function get_rows(): array {
		$all_people = $this->person_dao->get_all_in_competition( $this->competition->id );

		$people_filter     = $_GET['tuja_reports_people_filter'];
		$people_properties = $_GET['tuja_reports_people_properties'] ?? array( 'name' );

		switch ( $people_filter ) {
			case 'leaders_supervisors_admins':
				$selected_people = array_values(
					array_filter(
						$all_people,
						function ( Person $person ) {
							return $person->get_type() !== Person::PERSON_TYPE_REGULAR;
						}
					)
				);
				break;
			case 'all_competing':
				$selected_people = array_values(
					array_filter(
						$all_people,
						function ( Person $person ) {
							return $person->is_competing();
						}
					)
				);
				break;
			case 'everyone':
			default:
				$selected_people = $all_people;
				break;
		}

		$rows = array_map(
			function( Person $person ) use ( $people_properties ) {
				return array_combine(
					$people_properties,
					array_map(
						function( string $prop ) use ( $person ) {
							return $person->{$prop};
						},
						$people_properties,
					)
				);
			},
			$selected_people
		);
		return $rows;
	}

	function output_html( array $rows ) {
		$people = $rows;
		include 'views/report-people.php';
	}
}
