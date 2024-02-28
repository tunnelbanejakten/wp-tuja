<?php

namespace tuja\admin\reportgenerators;


use tuja\data\store\ExpenseReportDao;
use tuja\data\model\ExpenseReport;

class ReportExpenses extends AbstractListReport {
	private $dao;

	public function __construct() {
		parent::__construct();
		$this->dao = new ExpenseReportDao();
	}

	function get_rows(): array {
		return array_map( function ( ExpenseReport $expense_report ) {
			return [
				
				'random_id'    => strtoupper($expense_report->random_id),
				'description'  => $expense_report->description,
				'amount'       => number_format( $expense_report->amount / 100.0, 2, ',', '' ) . ' kr',
				'date'         => $expense_report->date->format('Y-m-d'),
				'name'         => $expense_report->name,
				'email'        => $expense_report->email,
				'bank_account' => $expense_report->bank_account,
			];
		}, $this->dao->get_all_in_competition( $this->competition->id ) );
	}

	function output_html( array $rows ) {
		$expense_reports = $rows;
		include( 'views/report-expenses.php' );
	}
}