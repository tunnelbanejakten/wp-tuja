<?php

namespace tuja\frontend;


use DateTime;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\util\paymentoption\PaymentOption;
use tuja\util\Strings;

class GroupPayment extends AbstractGroupView {
	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Betalningsinstruktioner för %s' );
	}

	function output() {
		$group = $this->get_group();

		$competition = $this->competition_dao->get( $this->get_group()->competition_id );

		$fee_calculator  = $competition->get_group_fee_calculator();
		$fee_amount      = $fee_calculator->calculate_fee( $group, new DateTime() );
		$fee_description = $fee_calculator->description();

		$payment_options = join( array_map( function ( PaymentOption $payment_option ) use ( $fee_amount, $group ) {
			return sprintf( '<h2>%s</h2>%s',
				Strings::get(
					'groups_payment.' . strtolower( ( new \ReflectionClass( $payment_option ) )->getShortName() ) . '.header' ),
				$payment_option->render( $group, $fee_amount ) );
		}, $competition->payment_options ) );

		$home_link = GroupHomeInitiator::link( $group );

		$email_link = sprintf( '<a href="mailto:%s">%s</a>', get_bloginfo( 'admin_email' ), get_bloginfo( 'admin_email' ) );

		include( 'views/group-payment.php' );
	}
}