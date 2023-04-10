<?php
namespace tuja\controller\payments;

use tuja\data\model\Group;
use tuja\data\model\payment\PaymentTransaction;

class MatchPaymentResult {
	public $transaction;
	public $best_match;
	public $best_match_reason;
	public function __construct( PaymentTransaction $transaction, Group $best_match = null, string $best_match_reason = '') {
		$this->transaction       = $transaction;
		$this->best_match        = $best_match;
		$this->best_match_reason = $best_match_reason;
	}
}
