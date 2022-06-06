<?php

namespace tuja\admin;


use tuja\admin\reportgenerators\ReportCheckInOut;
use tuja\admin\reportgenerators\ReportFoodPreferences;
use tuja\admin\reportgenerators\ReportGroupScore;
use tuja\admin\reportgenerators\ReportPayments;
use tuja\admin\reportgenerators\ReportNotes;
use tuja\admin\reportgenerators\ReportPeople;
use tuja\admin\reportgenerators\ReportQuestionFiltering;

class Reports extends Competition {
	private function get_report_url( $short_name, $format ) {
		return add_query_arg(
			array(
				'action'             => 'tuja_report',
				'tuja_competition'   => $this->competition->id,
				'tuja_view'          => $short_name,
				'tuja_report_format' => $format,
				'TB_iframe'          => 'true',
				'width'              => '900',
				'height'             => '500',
			),
			admin_url( 'admin.php' )
		);
	}

	private function report_config( $class_name, $name ) {
		$short_name     = substr( $class_name, strrpos( $class_name, '\\' ) + 1 );
		$config_file    = __DIR__ . '/reportgenerators/' . $short_name . '.config.json';
		$options_schema = file_exists( $config_file ) ? file_get_contents( $config_file ) : null;
		return array(
			'name'           => $name,
			'csv_url'        => $this->get_report_url( $short_name, 'csv' ),
			'html_url'       => $this->get_report_url( $short_name, 'html' ),
			'options_schema' => $options_schema,
		);
	}

	public function get_scripts(): array {
		return array(
			'admin-reports.js',
			'jsoneditor.min.js',
		);
	}

	public function output() {

		$competition = $this->competition;

		$reports = array(
			$this->report_config( ReportCheckInOut::class, 'In- och utcheckning' ),
			$this->report_config( ReportFoodPreferences::class, 'Matallergier och preferenser' ),
			$this->report_config( ReportGroupScore::class, 'Poängformulär' ),
			$this->report_config( ReportPayments::class, 'Förväntade inbetalningar' ),
			$this->report_config( ReportNotes::class, 'Meddelanden till tävlingsledningen' ),
			$this->report_config( ReportPeople::class, 'Deltagare och funktionärer' ),
			$this->report_config( ReportQuestionFiltering::class, 'get_filtered_questions' ),
		);

		include( 'views/reports.php' );
	}
}
