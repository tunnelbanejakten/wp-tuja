<?php

namespace tuja\admin;


use tuja\data\store\CompetitionDao;

class Reports {
	private $competition;
	private $competition_dao;

	public function __construct() {
		$this->competition_dao = new CompetitionDao();

		$this->competition = $this->competition_dao->get( $_GET['tuja_competition'] );

		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}

	private function get_report_url( $report_class_name, $format ) {
		return add_query_arg( array(
			'action'             => 'tuja_report',
			'tuja_competition'   => $this->competition->id,
			'tuja_view'          => substr( $report_class_name, strrpos( $report_class_name, '\\' ) + 1 ),
			'tuja_report_format' => $format,
			'TB_iframe'          => 'true',
			'width'              => '900',
			'height'             => '500'
		), admin_url( 'admin.php' ) );
	}

	private function report_config( $class_name, $name ) {
		return [
			'name'     => $name,
			'csv_url'  => $this->get_report_url( $class_name, 'csv' ),
			'html_url' => $this->get_report_url( $class_name, 'html' )
		];
	}

	public function output() {

		$competition = $this->competition;

		$reports = [
			$this->report_config( ReportCheckInOut::class, 'In- och utcheckning' ),
			$this->report_config( ReportFoodPreferences::class, 'Matallergier och preferenser' ),
			$this->report_config( ReportGroupScore::class, 'Poängformulär' )
		];

		include( 'views/reports.php' );
	}
}