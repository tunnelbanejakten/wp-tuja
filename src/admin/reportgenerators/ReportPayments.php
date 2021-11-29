<?php

namespace tuja\admin\reportgenerators;

use DateTime;
use tuja\data\model\Person;
use tuja\data\store\AbstractDao;
use tuja\data\store\GroupDao;
use tuja\data\model\Group;
use tuja\data\store\PersonDao;
use tuja\util\paymentoption\PaymentOption;

class ReportPayments extends AbstractListReport {
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

		return array_reduce(
			$groups,
			function ( array $acc, Group $group ) use ( $date, $fee_calculator ) {
				$amount = ( $group->fee_calculator ?? $fee_calculator )->calculate_fee( $group, $date ?: new DateTime() );
				if ( $amount > 0 ) {

					$references = array_reduce(
						$this->competition->payment_options,
						function ( $refs, PaymentOption $payment_option ) use ( $group ) {
							$column_name          = 'reference_' . strtolower( ( new \ReflectionClass( $payment_option ) )->getShortName() );
							$refs[ $column_name ] = $payment_option->get_payment_reference( $group );
							return $refs;
						},
						array()
					);

					$acc[] = array_merge(
						array(
							'name'            => $group->name,
							'count_competing' => $group->count_competing,
							'count_follower'  => $group->count_follower,
							'amount'          => $amount,
						),
						$references
					);
				}
				return $acc;
			},
			array()
		);
	}

	function output_html( array $rows ) {
		$groups = $rows;
		include 'views/report-payments.php';
	}
}
