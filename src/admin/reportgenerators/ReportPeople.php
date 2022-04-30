<?php

namespace tuja\admin\reportgenerators;

use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\GroupDao;
use tuja\data\store\PersonDao;

class ReportPeople extends AbstractListReport {
	private $person_dao;
	private $groups_dao;

	public function __construct() {
		parent::__construct();
		$this->person_dao = new PersonDao();
		$this->groups_dao = new GroupDao();
	}

	function get_rows(): array {
		$active_groups     = $this->groups_dao->get_all_in_competition( $this->competition->id, false, null );
		$active_groups_ids = array_map(
			function ( Group $group ) {
				return $group->id;
			},
			$active_groups
		);

		$all_people                  = $this->person_dao->get_all_in_competition( $this->competition->id, false, null );
		$all_people_in_active_groups = array_filter(
			$all_people,
			function ( Person $person ) use ( $active_groups_ids ) {
				return in_array( $person->group_id, $active_groups_ids );
			}
		);

		$people_filter     = $_GET['tuja_reports_people_filter'];
		$people_properties = $_GET['tuja_reports_people_properties'] ?? array( 'name' );
		$groups_data       = array_reduce(
			$active_groups,
			function ( array $res, Group $group ) {
				$res[ $group->id ] = array(
					'group_category' => $group->get_category()->name,
					'group_name'     => $group->name,
				);
				return $res;
			},
			array()
		);

		switch ( $people_filter ) {
			case 'leaders_supervisors_admins':
				$selected_people = array_values(
					array_filter(
						$all_people_in_active_groups,
						function ( Person $person ) {
							return $person->get_type() !== Person::PERSON_TYPE_REGULAR;
						}
					)
				);
				break;
			case 'all_competing':
				$selected_people = array_values(
					array_filter(
						$all_people_in_active_groups,
						function ( Person $person ) {
							return $person->is_competing();
						}
					)
				);
				break;
			case 'crew':
				$crew_group_ids  = array_map(
					function ( Group $group ) {
						return $group->id;
					},
					array_filter(
						$active_groups,
						function ( Group $group ) {
							return $group->get_category()->get_rules()->is_crew();
						}
					)
				);
				$selected_people = array_values(
					array_filter(
						$all_people_in_active_groups,
						function ( Person $person ) use ( $crew_group_ids ) {
							return in_array( intval( $person->group_id ), $crew_group_ids, true );
						}
					)
				);
				break;
			case 'everyone':
			default:
				$selected_people = $all_people_in_active_groups;
				break;
		}

		$rows = array_map(
			function( Person $person ) use ( $people_properties, $groups_data ) {
				return array_combine(
					$people_properties,
					array_map(
						function( string $prop ) use ( $person, $groups_data ) {
							switch ( $prop ) {
								case 'name':
								case 'pno':
								case 'email':
								case 'phone':
									return $person->{$prop};
								case 'role':
									return $person->get_type();
								case 'group_category':
								case 'group_name':
									$props = $groups_data[ intval( $person->group_id ) ];
									return $props[ $prop ];
							}
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
