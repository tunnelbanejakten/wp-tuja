<?php

namespace tuja\admin\reportgenerators;

use DateTime;
use tuja\controller\PaymentsController;
use tuja\data\model\Person;
use tuja\data\store\AbstractDao;
use tuja\data\store\GroupDao;
use tuja\data\model\Group;
use tuja\data\model\payment\GroupPayment;
use tuja\data\store\PaymentDao;
use tuja\data\store\PersonDao;
use tuja\util\paymentoption\PaymentOption;

class ReportPayments extends AbstractListReport {
	private $group_dao;
	private $payment_dao;

	public function __construct() {
		parent::__construct();
		$this->group_dao   = new GroupDao();
		$this->payment_dao = new PaymentDao();
	}

	function get_rows(): array {
		$date = isset( $_GET['tuja_reports_date'] ) ? AbstractDao::from_db_date( $_GET['tuja_reports_date'] ) : null;

		$groups = $this->group_dao->get_all_in_competition(
			$this->competition->id,
			false,
			$date
		);

		$group_payments = $this->payment_dao->get_group_payments( $this->competition->id );

		$payments_controller = new PaymentsController( $this->competition );

		return array_reduce(
			$groups,
			function ( array $acc, Group $group ) use ( $date, $group_payments, $payments_controller ) {
				list ($amount_expected, $amount_paid, $status_message) = $payments_controller->group_fee_status(
					$group,
					$group_payments[ $group->id ] ?? array(),
					$date ?: new DateTime()
				);

				if ( $amount_expected > 0 ) {

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
							'name'                  => $group->name,
							'count_competing'       => $group->count_competing,
							'count_follower'        => $group->count_follower,
							'amount_expected'       => $amount_expected,
							'amount_paid'           => $amount_paid,
							'amount_status_message' => $status_message,
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
