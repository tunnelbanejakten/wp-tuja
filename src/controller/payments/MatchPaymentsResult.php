<?php
namespace tuja\controller\payments;

class MatchPaymentsResult {
	public $transactions;
	public function __construct( array $transactions ) {
		$this->transactions = $transactions;
	}
}
