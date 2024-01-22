<?php

namespace tuja\admin\reportgenerators;


use tuja\data\store\GroupDao;
use tuja\data\model\Group;
use tuja\util\Id;

class ReportExpenseReports extends AbstractListReport {
	public function __construct() {
		parent::__construct();
	}

	function get_rows(): array {
		$copies = intval($_GET['tuja_reports_copies']);
		$id_generator = new Id();
		return array_map(function () use ($copies, $id_generator) {
			return array('key' => strtoupper($id_generator->random_unambiguous_letters(4)));
		}, array_fill(0, $copies, null));
	}

	function output_html( array $rows ) {
		$title = $this->competition->name;
		include( 'views/report-expensereports.php' );
	}
}