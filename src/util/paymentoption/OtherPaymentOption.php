<?php


namespace tuja\util\paymentoption;


use tuja\data\model\Group;
use tuja\util\Template;

class OtherPaymentOption implements PaymentOption {

	private $message_template;

	function get_payment_reference( Group $group ): string {
		$params = array_merge(
			Template::group_parameters( $group ),
			Template::site_parameters()
		);
		return Template::string( $this->message_template )->render( $params );
	}

	function render( Group $group, int $fee ): string {
		return sprintf( '<p>%s</p>', $message = $this->get_payment_reference( $group ));
	}

	function get_config_json_schema() {
		return
			[
				"message_template" => [
					"title"  => 'Text att visa',
					"type"   => "string"
				]
			];
	}

	function get_default_config() {
		return [
			"message_template" => ""
		];
	}

	function configure( $config ) {
		$defaults               = $this->get_default_config();
		$this->message_template = $config['message_template'] ?: $defaults['message_template'];
	}

	function get_config() {
		return [
			"message_template" => $this->message_template,
		];
	}
}