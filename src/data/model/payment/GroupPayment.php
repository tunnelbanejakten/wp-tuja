<?php
namespace tuja\data\model\payment;

class GroupPayment {
	public $id;
	public $team_id;
	public $amount;
	public $note;
	public $paymenttransaction_id;

	public function __construct(
		$id,
		$team_id,
		$amount,
		$note,
		$paymenttransaction_id
	) {
		$this->id                    = $id;
		$this->team_id               = $team_id;
		$this->amount                = $amount;
		$this->note                  = $note;
		$this->paymenttransaction_id = $paymenttransaction_id;
	}
}
