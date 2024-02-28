<?php

namespace tuja\data\model;

use Exception;
use tuja\util\DateUtils;
use tuja\util\Id;
use tuja\util\Random;
use tuja\util\rules\GroupCategoryRules;
use tuja\util\StateMachine;
use tuja\util\StateMachineException;
use tuja\controller\ExpenseReportController;

class ExpenseReport {
	public $competition_id;
	public $random_id;
	public $description;
	public $amount;
	public $date;
	public $name;
	public $email;
	public $bank_account;

	public function validate() {
		if ( strlen( $this->name ) > 100 ) {
			throw new ValidationException( 'name', 'name får inte vara längre än 100 tecken.' );
		}
		if ( strlen( $this->email ) > 100 ) {
			throw new ValidationException( 'email', 'email får inte vara längre än 100 tecken.' );
		}
		if ( ! Person::is_valid_email_address( $this->email ) ) {
			throw new ValidationException( 'email', 'email verkar inte vara en giltig adress.' );
		}
		if ( ! ExpenseReportController::is_id( $this->random_id ) ) {
			throw new ValidationException( 'random_id', 'Ogiltigt id.' );
		}
		if ( strlen( $this->bank_account ) > 100 ) {
			throw new ValidationException( 'bank_account', 'bank_account får inte vara längre än 100 tecken.' );
		}
	}

}
