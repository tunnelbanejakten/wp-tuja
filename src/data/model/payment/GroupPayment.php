<?php
namespace tuja\data\model\payment;

class GroupPayment {
	private $id;
	private $team_id;
	private $amount;
	private $note;
	private $paymenttransaction_id;

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
