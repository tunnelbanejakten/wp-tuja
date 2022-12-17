<?php

namespace tuja\admin\reportgenerators;


use tuja\data\store\GroupDao;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\PersonDao;

class ReportCheckInOut extends AbstractListReport {
	private $group_dao;

	public function __construct() {
		parent::__construct();
		$this->group_dao  = new GroupDao();
		$this->person_dao = new PersonDao();
	}

	function get_rows(): array {
		$people = $this->person_dao->get_all_in_competition( $this->competition->id, false );
		return array_map(
			function ( Group $group ) use ( $people ) {
				$referrals = count(
					array_filter(
						$people,
						function ( Person $person ) use ( $group ) {
							return $person->referrer_team_id === $group->id;
						}
					)
				);
				return array(
					'name'           => $group->name,
					'category'       => $group->get_category()->name,
					'referral_count' => $referrals,
				);
			},
			array_filter(
				$this->group_dao->get_all_in_competition( $this->competition->id ),
				function ( Group $group ) {
					return ! $group->get_category()->get_rules()->is_crew();
				}
			)
		);
	}

	function output_html( array $rows ) {
		$groups = $rows;
		include( 'views/report-checkinout.php' );
	}
}
