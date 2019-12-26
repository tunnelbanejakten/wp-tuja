<?php

namespace tuja\admin;

use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\data\model\Station;
use tuja\data\store\StationDao;
use tuja\util\score\ScoreCalculator;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\CompetitionDao;
use tuja\data\model\ValidationException;

class Stations {

	private $competition;

	public function __construct() {
		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $_GET['tuja_competition'] );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}


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

		$station_dao   = new StationDao();

		$competition = $this->competition;

		$stations  = $station_dao->get_all_in_competition( $competition->id );

		include( 'views/stations.php' );
	}
}