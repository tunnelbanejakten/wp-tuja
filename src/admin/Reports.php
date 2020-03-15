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

	private function get_report_url( $short_name, $format ) {
		return add_query_arg( array(
			'action'             => 'tuja_report',
			'tuja_competition'   => $this->competition->id,
			'tuja_view'          => $short_name,
			'tuja_report_format' => $format,
			'TB_iframe'          => 'true',
			'width'              => '900',
			'height'             => '500'
		), admin_url( 'admin.php' ) );
	}

	private function report_config( $class_name, $name ) {
		$short_name     = substr( $class_name, strrpos( $class_name, '\\' ) + 1 );
		$options_schema = file_get_contents( __DIR__ . '/' . $short_name . '.config.json' );
		return [
			'name'           => $name,
			'csv_url'        => $this->get_report_url( $short_name, 'csv' ),
			'html_url'       => $this->get_report_url( $short_name, 'html' ),
			'options_schema' => $options_schema
		];
	}

	public function output() {

		$competition = $this->competition;

		$reports = [
			$this->report_config( ReportCheckInOut::class, 'In- och utcheckning' ),
			$this->report_config( ReportFoodPreferences::class, 'Matallergier och preferenser' ),
			$this->report_config( ReportGroupScore::class, 'Poängformulär' ),
			$this->report_config( ReportPayments::class, 'Förväntade inbetalningar' ),
			$this->report_config( ReportNotes::class, 'Meddelanden till tävlingsledningen' ),
			$this->report_config( ReportQuestionFiltering::class, 'get_filtered_questions' )
		];

		include( 'views/reports.php' );
	}
}