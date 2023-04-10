<?php

namespace tuja\admin;

use tuja\data\store\GroupDao;
use tuja\data\store\PaymentDao;

class PaymentsStatus extends Payments {
	private $group_dao;

	public function __construct() {
		parent::__construct();
		$this->group_dao = new GroupDao();
	}

	public function output() {
		$transactions   = $this->payment_dao->get_all_in_competition( $this->competition->id );
		$group_payments = $this->payment_dao->get_group_payments( $this->competition->id );
		$groups         = $this->group_dao->get_all_in_competition( $this->competition->id );

		include( 'views/payments-status.php' );
	}
}
