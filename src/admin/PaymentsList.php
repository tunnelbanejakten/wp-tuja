<?php

namespace tuja\admin;

use tuja\data\store\PaymentDao;

class PaymentsList extends Payments {
	public function __construct() {
		parent::__construct();
	}

	public function output() {
		$transaction = $this->payment_dao->get_all_in_competition( $this->competition->id );

		include( 'views/payments-list.php' );
	}
}
