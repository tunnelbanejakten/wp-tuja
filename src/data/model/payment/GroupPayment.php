<?php
namespace tuja\data\model\payment;

class GroupPayment {
	public $id;
	public $team_id;
	public $amount;
	public $note;
	public $paymenttransaction_id;
	private $paymenttransaction_description;

	public function __construct(
		$id,
		$team_id,
		$amount,
		$note,
		$paymenttransaction_id,
		$paymenttransaction_description
	) {
		$this->id                             = $id;
		$this->team_id                        = $team_id;
		$this->amount                         = $amount;
		$this->note                           = $note;
		$this->paymenttransaction_id          = $paymenttransaction_id;
		$this->paymenttransaction_description = $paymenttransaction_description;
	}

	public function get_paymenttransaction_description(): string {
		return $this->paymenttransaction_description ?? '';
	}
}
