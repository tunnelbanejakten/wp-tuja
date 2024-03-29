<?php
namespace tuja\data\model\payment;

use DateTimeInterface;

class PaymentTransaction {
	public $id;
	public $competition_id;
	public $key;
	public $transaction_time;
	public $message;
	public $sender;
	public $amount;
	public $groups_attribution_sum;

	public function __construct(
		int $id,
		int $competition_id,
		string $key,
		DateTimeInterface $transaction_time,
		string $message,
		string $sender,
		int $amount = 0,
		int $groups_attribution_sum = 0
	) {
		$this->id                     = $id;
		$this->competition_id         = $competition_id;
		$this->key                    = $key;
		$this->transaction_time       = $transaction_time;
		$this->message                = $message;
		$this->sender                 = $sender;
		$this->amount                 = $amount;
		$this->groups_attribution_sum = $groups_attribution_sum;
	}

	public function validate() {
		// No-op for now.
	}
}
