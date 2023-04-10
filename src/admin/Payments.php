<?php

namespace tuja\admin;

use tuja\data\store\PaymentDao;

class Payments extends Competition {
	protected $payment_dao;

	public function __construct() {
		parent::__construct();

		$this->payment_dao = new PaymentDao();
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		return $this->add_static_menu(
			$menu,
			array(
				PaymentsList::class   => array( 'Transaktioner', null ),
				PaymentsImport::class => array( 'Importera', null ),
				PaymentsMatch::class  => array( 'Koppla transaktioner till lag', null ),
				PaymentsStatus::class => array( 'Betalningsstatus för lag', null ),
			)
		);
	}

	public function output() {
		include( 'views/payments.php' );
	}
}
