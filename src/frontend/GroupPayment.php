<?php

namespace tuja\frontend;


use DateTime;
use tuja\controller\PaymentsController;
use tuja\data\store\PaymentDao;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\util\paymentoption\PaymentOption;
use tuja\util\Strings;

class GroupPayment extends AbstractGroupView {
	private $payment_dao;

	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Betalningsinstruktioner för %s' );
		$this->payment_dao = new PaymentDao();
	}

	function output() {
		$group = $this->get_group();

		$competition = $this->competition_dao->get( $this->get_group()->competition_id );

		$group_payments      = $this->payment_dao->get_group_payments( $competition->id );
		$payments_controller = new PaymentsController( $competition->id );

		list (
			$fee_amount,
			$fee_paid,
			,
			$fee_description
			) = $payments_controller->group_fee_status(
				$group,
				$group_payments[ $group->id ] ?? array(),
				new DateTime()
			);

		$is_fee_fully_paid = $fee_amount === $fee_paid;

		$payment_options = join(
			array_map(
				function ( PaymentOption $payment_option ) use ( $fee_amount, $group ) {
					return sprintf(
						'<h2>%s</h2>%s',
						Strings::get(
							'groups_payment.' . strtolower( ( new \ReflectionClass( $payment_option ) )->getShortName() ) . '.header'
						),
						$payment_option->render( $group, $fee_amount )
					);
				},
				$competition->payment_options
			)
		);

		$home_link = GroupHomeInitiator::link( $group );

		$email_link = sprintf( '<a href="mailto:%s">%s</a>', get_bloginfo( 'admin_email' ), get_bloginfo( 'admin_email' ) );

		include( 'views/group-payment.php' );
	}
}
