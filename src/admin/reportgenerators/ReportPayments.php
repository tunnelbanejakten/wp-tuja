<?php

namespace tuja\admin\reportgenerators;


use DateTime;
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

		$fee_calculator = $this->competition->get_group_fee_calculator();

		$groups = $this->group_dao->get_all_in_competition(
			$this->competition->id,
			false,
			$date
		);

		return array_reduce( $groups, function ( array $acc, Group $group ) use ( $date, $fee_calculator ) {
			$people = ( new PersonDao() )->get_all_in_group( $group->id, false, $date );

			$amount = $fee_calculator->calculate_fee( $group, $date ?: new DateTime() );
			if ( $amount > 0 ) {
				$acc[] = [
					'name'            => $group->name,
					'reference'       => 'TSL20 ' . $group->name,
					'count_competing' => $group->count_competing,
					'count_follower'  => $group->count_follower,
					'amount'          => $amount,
					'people'          => array_map( function ( Person $person ) {
						return $person->name;
					}, $people )
				];
			}

			return $acc;

		}, [] );
	}

	function output_html( array $rows ) {
		$groups = $rows;
		include( 'views/report-payments.php' );
	}
}