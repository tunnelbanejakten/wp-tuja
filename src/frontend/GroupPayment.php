<?php

namespace tuja\frontend;


use DateTime;
use Exception;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\util\SwishQrCode;

class GroupPayment extends AbstractGroupView {
	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Betalningsinstruktioner för %s' );
	}

	function output() {
		$group = $this->get_group();

		$competition = $this->competition_dao->get( $this->get_group()->competition_id );

		$fee_calculator = $competition->get_group_fee_calculator();

		$amount      = $fee_calculator->calculate_fee( $group, new DateTime() );
		$description = $fee_calculator->description();

		$swish_payee   = '1231702646';
		$swish_message = 'TSL20 ' . $group->name;
		try {
			$swish_qr_code_image_url = ( new SwishQrCode() )->swish_qr_code_image_url( $swish_payee, $amount, $swish_message );
		} catch ( Exception $e ) {
			$swish_qr_code_image_url = '';
		}

		$home_link = GroupHomeInitiator::link( $group );

		$email_link = sprintf( '<a href="mailto:%s">%s</a>', get_bloginfo( 'admin_email' ), get_bloginfo( 'admin_email' ) );

		include( 'views/group-payment.php' );
	}
}