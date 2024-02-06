<?php

namespace tuja\admin\reportgenerators;


use tuja\data\store\GroupDao;
use tuja\data\model\Group;
use tuja\controller\ExpenseReportController;
use tuja\frontend\router\ExpenseReportInitiator;

class ReportExpenseReports extends AbstractListReport {
	public function __construct() {
		parent::__construct();
	}

	function get_rows(): array {
		$copies = intval($_GET['tuja_reports_copies']);
		return array_map(function () {
			$key = strtoupper(ExpenseReportController::get_new_id());
			return array(
				'key' => $key,
				'form_link' => ExpenseReportInitiator::link( $this->competition, $key )
			);
		}, array_fill(0, $copies, null));
	}

	public function get_scripts(): array {
		return array(
			'report-expensereports.js',
			'qrious-4.0.2.min.js',
		);
	}

	function output_html( array $rows ) {
		$title = $this->competition->name;
		include( 'views/report-expensereports.php' );
	}
}