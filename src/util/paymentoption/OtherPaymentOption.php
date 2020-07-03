<?php


namespace tuja\util\paymentoption;


use tuja\data\model\Group;
use tuja\util\Template;

class OtherPaymentOption implements PaymentOption {

	private $message_template;

	function render( Group $group, int $fee ): string {
		// TODO: Implement render() method.
		$params = array_merge(
			Template::group_parameters( $group ),
			Template::site_parameters()
		);

		return sprintf( '<p>%s</p>', $message = Template::string( $this->message_template )->render( $params ) );
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