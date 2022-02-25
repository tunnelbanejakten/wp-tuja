<?php

namespace tuja\admin;

use tuja\data\store\CompetitionDao;
use tuja\data\store\StationDao;

class Station {

	private $station;
	private $competition;
	private $station_dao;

	public function __construct() {
		$this->station_dao = new StationDao();
		$this->station     = $this->station_dao->get( $_GET['tuja_station'] );
		if ( ! $this->station ) {
			print 'Could not find station';

			return;
		}

		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $this->station->competition_id );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}


	public function handle_post() {
		if ( ! isset( $_POST['tuja_station_action'] ) ) {
			return;
		}

		$action = @$_POST['tuja_station_action'];
		if ( $action === 'save' ) {
			$this->station->name = @$_POST['tuja_station_name'];

			$success = $this->station_dao->update( $this->station );

			if ( $success ) {
				$this->station = $this->station_dao->get( $_GET['tuja_station'] );
				AdminUtils::printSuccess( 'Ändringar sparade.' );
			} else {
				AdminUtils::printError( 'Kunde inte spara.' );
			}
		}
	}

	public function output() {
		$this->handle_post();

		$competition = $this->competition;
		$station     = $this->station;
		$back_url    = add_query_arg(
			array(
				'tuja_competition' => $competition->id,
				'tuja_view'        => 'Stations',
			)
		);

		include 'views/station.php';
	}
}
