<?php

namespace tuja\data\store;

use DateTime;
use tuja\data\model\ExpenseReport;
use tuja\data\model\Competition;
use tuja\util\Database;

class ExpenseReportDao extends AbstractDao {

	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'expense_report' );
	}

	function create( ExpenseReport $expense_report ) {
		$expense_report->validate();

		$affected_rows = $this->wpdb->insert(
			$this->table,
			array(
				'competition_id' => $expense_report->competition_id,
				'random_id'      => $expense_report->random_id,
				'description'    => $expense_report->description,
				'amount'         => $expense_report->amount,
				'date'           => self::to_db_date( $expense_report->date ),
				'name'           => $expense_report->name,
				'email'          => $expense_report->email,
				'bank_account'   => $expense_report->bank_account,
			),
			array(
				'%d',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
			)
		);
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success;
	}

	function get( Competition $competition, string $random_id ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_expense_report( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE competition_id = %d AND random_id = %s',
			$competition->id,
			$random_id
		);
	}

	protected static function to_expense_report( $result ): ExpenseReport {
		$expense_report                 = new ExpenseReport();
		$expense_report->competition_id = intval( $result->competition_id );
		$expense_report->random_id      = $result->random_id;
		$expense_report->description    = $result->description;
		$expense_report->amount         = intval($result->amount);
		$expense_report->date           = self::from_db_date( $result->date );
		$expense_report->name           = $result->name;
		$expense_report->email          = $result->email;
		$expense_report->bank_account   = $result->bank_account;

		return $expense_report;
	}
}
