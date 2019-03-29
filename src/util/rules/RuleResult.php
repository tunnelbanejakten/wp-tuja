<?php

namespace tuja\util\rules;


class RuleResult {

	const OK = 'ok';
	const WARNING = 'warning';
	const BLOCKER = 'blocker';

	public $rule_name;
	public $status;
	public $details;

	public function __construct( $rule_name, $status, $details ) {
		$this->rule_name = $rule_name;
		$this->status    = $status;
		$this->details   = $details;
	}
}