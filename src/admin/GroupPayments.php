<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Event;
use tuja\data\store\EventDao;
use tuja\data\store\PaymentDao;

class GroupPayments extends Group {
	private $payment_dao;

	public function __construct() {
		parent::__construct();

		$this->payment_dao = new PaymentDao();
	}

	public function handle_post() {
		global $wpdb;

		if ( ! isset( $_POST['tuja_payment_action'] ) ) {
			return;
		}

		@list( $action, $parameter ) = explode( '__', @$_POST['tuja_payment_action'] );

		if ( $action === 'delete_payment' ) {
			$success = $this->payment_dao->delete_group_payment( intval( $parameter ) );

			if ( $success ) {
				AdminUtils::printSuccess( 'Inbetalningen har tagits bort.' );
			} else {
				AdminUtils::printError( 'Kunde inte ta bort inbetalningen.' );
				if ( $error = $wpdb->last_error ) {
					AdminUtils::printError( $error );
				}
			}
		} elseif ( $action === 'create_payment' ) {
			$payment_id = $this->payment_dao->create_group_payment(
				$this->group->id,
				intval( $_POST['tuja_create_payment_amount'] ) * 100,
				0,
				$_POST['tuja_create_payment_note']
			);

			$success = $payment_id !== false;

			if ( $success ) {
				AdminUtils::printSuccess( 'Inbetalningen har registrerats.' );
			} else {
				AdminUtils::printError( 'Kunde inte registrera inbetalningen.' );
				if ( $error = $wpdb->last_error ) {
					AdminUtils::printError( $error );
				}
			}
		}
	}

	public function output() {
		$this->handle_post();

		$group       = $this->group;
		$competition = $this->competition;

		$payments = $this->payment_dao->get_group_payments_by_group( $group );

		include 'views/group-payments.php';
	}
}
