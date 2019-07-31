<?php

namespace tuja\admin;


use tuja\data\store\GroupDao;
use tuja\data\model\Group;

class ReportCheckInOut extends AbstractReport {
	private $group_dao;

	public function __construct() {
		parent::__construct();
		$this->group_dao = new GroupDao();
	}

	function get_rows(): array {
		return array_map( function ( Group $group ) {
			return [
				'name'     => $group->name,
				'category' => $group->get_derived_group_category()->name
			];
		}, array_filter(
			$this->group_dao->get_all_in_competition( $this->competition->id ),
			function ( Group $group ) {
				return ! $group->get_derived_group_category()->is_crew;
			} ) );
	}

	function output_html( array $rows ): array {
		$groups = $rows;
		include( 'views/report-checkinout.php' );
	}
}