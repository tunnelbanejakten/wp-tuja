<?php

namespace tuja\admin;


use tuja\data\model\Person;
use tuja\data\store\AbstractDao;
use tuja\data\store\GroupDao;
use tuja\data\model\Group;
use tuja\data\store\PersonDao;

class ReportPayments extends AbstractReport {
	private $group_dao;

	public function __construct() {
		parent::__construct();
		$this->group_dao = new GroupDao();
	}

	function get_rows(): array {
		$date = isset( $_GET['tuja_reports_date'] ) ? AbstractDao::from_db_date( $_GET['tuja_reports_date'] ) : null;
		$fee_competing = isset( $_GET['tuja_reports_fee_competing'] ) ? intval($_GET['tuja_reports_fee_competing']) : 0;

		return array_map( function ( Group $group ) use ( $date, $fee_competing ) {
			$people = ( new PersonDao() )->get_all_in_group( $group->id, $date );

			$amount = $fee_competing * $group->count_competing;

			return [
				'name'            => $group->name,
				'reference'       => 'TSL20 ' . $group->name,
				'count_competing' => $group->count_competing,
				'count_follower'  => $group->count_follower,
				'amount'          => number_format( $amount, 2, ',', ' ' ),
				'people'          => array_map( function ( Person $person ) {
					return $person->name;
				}, $people )
			];
		}, array_filter(
			$this->group_dao->get_all_in_competition(
				$this->competition->id,
				$date
			),
			function ( Group $group ) {
				return ! $group->get_derived_group_category()->is_crew;
			} ) );
	}

	function output_html( array $rows ) {
		$groups = $rows;
		include( 'views/report-payments.php' );
	}
}