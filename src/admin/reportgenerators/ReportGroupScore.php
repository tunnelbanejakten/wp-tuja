<?php

namespace tuja\admin\reportgenerators;


use tuja\data\store\GroupDao;
use tuja\data\model\Group;

class ReportGroupScore extends AbstractReport {
	private $group_dao;

	public function __construct() {
		parent::__construct();
		$this->group_dao = new GroupDao();
	}

	function get_rows(): array {
		return array_map( function ( Group $group ) {
			return [
				'name'     => $group->name,
				'category' => $group->get_category()->name
			];
		}, array_filter( $this->group_dao->get_all_in_competition( $this->competition->id ), function ( Group $group ) {
			return ! $group->get_category()->get_rules()->is_crew();
		} ) );
	}

	function output_html( array $rows ) {
		$groups = $rows;
		include( 'views/report-groupscore.php' );
	}
}