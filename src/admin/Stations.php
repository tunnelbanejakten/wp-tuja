<?php

namespace tuja\admin;

use tuja\data\model\Station;
use tuja\data\store\StationDao;
use tuja\data\model\ValidationException;

class Stations extends AbstractStation {

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] == 'station_create' ) {
			$props                 = new Station();
			$props->name           = $_POST['tuja_station_name'];
			$props->competition_id = $this->competition->id;
			try {
				$station_dao = new StationDao();
				$station_dao->create( $props );
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			}
		}
	}


	public function output() {
		$this->handle_post();

		$station_dao = new StationDao();

		$competition = $this->competition;

		$stations = $station_dao->get_all_in_competition( $competition->id );

		$ticketing_url = add_query_arg(
			array(
				'tuja_view' => 'StationsTicketing',
			)
		);
		$points_url    = add_query_arg(
			array(
				'tuja_view' => 'StationsPoints',
			)
		);
		$manage_tickets_url    = add_query_arg(
			array(
				'tuja_view' => 'StationsManageTickets',
			)
		);
		include( 'views/stations.php' );
	}
}
