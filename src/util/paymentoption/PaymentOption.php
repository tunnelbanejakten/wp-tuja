<?php


namespace tuja\util\paymentoption;


use tuja\data\model\Group;

interface PaymentOption {
	function render(Group $group, int $fee): string;

	function get_payment_reference(Group $group): string;

	function configure( $config );

	function get_config_json_schema();

	function get_config();

	function get_default_config();
}