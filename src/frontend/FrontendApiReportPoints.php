<?php

namespace tuja\frontend;

use tuja\controller\ReportPointsController;
use tuja\data\store\GroupDao;
use tuja\data\store\StationDao;
use tuja\util\concurrency\LockValuesList;

class FrontendApiReportPoints {
	public static function station_points_get_all() {
		// TODO: Input validation
		$station = $_POST['station'];
		$user    = $_POST['user'];

		$controller = new ReportPointsController();

		// Is user a crew member?
		self::check_authorized( $controller, $user );

		$station = ( new StationDao() )->get( $_POST['station'] );

		wp_send_json(
			$controller->get_all_points( $station ),
			200
		);
		exit;
	}

	public static function station_points_set() {
		// TODO: Input validation
		$station = $_POST['station'];
		$group   = $_POST['group'];
		$user    = $_POST['user'];
		$lock    = $_POST['lock'];
		$points  = $_POST['points'];

		$controller = new ReportPointsController();

		// Is user a crew member?
		self::check_authorized( $controller, $user );

		$station = ( new StationDao() )->get( $_POST['station'] );
		$group   = ( new GroupDao() )->get( $_POST['group'] );

		$result = $controller->set_points( $station, $group, $points, LockValuesList::from_string( $lock ) );
		wp_send_json( $result, $result['http_status'] );
		exit;
	}

	private static function check_authorized( ReportPointsController $controller, string $user_key ) {
		if ( ! $controller->is_authorized( $user_key ) ) {
			wp_send_json(
				array(
					'message' => 'No access',
				),
				401
			);
			exit;
		}
	}
}
