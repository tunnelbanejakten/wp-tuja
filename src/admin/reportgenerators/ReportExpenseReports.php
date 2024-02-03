<?php

namespace tuja\admin\reportgenerators;


use tuja\data\store\GroupDao;
use tuja\data\model\Group;
use tuja\controller\ExpenseReportController;

class ReportExpenseReports extends AbstractListReport {
	public function __construct() {
		parent::__construct();
	}

	function get_rows(): array {
		$copies = intval($_GET['tuja_reports_copies']);
		return array_map(function () {
			return array('key' => strtoupper(ExpenseReportController::get_new_id()));
		}, array_fill(0, $copies, null));
	}

	function output_html( array $rows ) {
		$title = $this->competition->name;
		include( 'views/report-expensereports.php' );
	}
}