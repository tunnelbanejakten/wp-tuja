<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\data\model\Points;
use tuja\data\model\Station;
use tuja\data\model\StationWeight;
use tuja\data\model\TicketDesign;
use tuja\data\store\StationDao;
use tuja\data\store\TicketDao;
use tuja\util\score\ScoreCalculator;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\CompetitionDao;
use tuja\data\model\ValidationException;
use tuja\data\store\StationPointsDao;

class StationsPoints {


	const ACTION_SAVE = 'save';

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

		if ( self::ACTION_SAVE === $_POST['tuja_action'] ) {
			$this->handle_action_save();
			try {
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
			}
		}
	}

	private function handle_action_save() {
		$station_dao        = new StationDao();
		$group_dao          = new GroupDao();
		$station_points_dao = new StationPointsDao();

		$stations = $station_dao->get_all_in_competition( $this->competition->id );
		$groups   = $group_dao->get_all_in_competition( $this->competition->id );

		array_walk(
			$stations,
			function ( Station $station ) use ( $station_points_dao, $groups ) {
				array_walk(
					$groups,
					function ( Group $group ) use ( $station_points_dao, $station ) {
						$key = self::get_field_key( $station->id, $group->id );
						if ( isset( $_POST[ $key ] ) && is_numeric( $_POST[ $key ] ) ) {
							$points = intval( $_POST[ $key ] );
							$station_points_dao->set( $group->id, $station->id, $points );
						} else {
							$station_points_dao->set( $group->id, $station->id, null );
						}
					}
				);
			}
		);
	}

	private static function get_field_key( $station_id, $group_id ) {
		return join( '__', array( 'tuja', 'station-points', $station_id, $group_id ) );
	}

	public function get_scripts(): array {
		return array();
	}

	public function output() {
		$this->handle_post();

		$station_dao        = new StationDao();
		$group_dao          = new GroupDao();
		$station_points_dao = new StationPointsDao();

		$competition = $this->competition;

		$stations = $station_dao->get_all_in_competition( $this->competition->id );
		$groups   = $group_dao->get_all_in_competition( $this->competition->id );

		$points_by_key = array();
		$points        = $station_points_dao->get_by_competition( $this->competition->id );
		array_walk(
			$points,
			function ( Points $points ) use ( &$points_by_key ) {
				$key                   = self::get_field_key( $points->station_id, $points->group_id );
				$points_by_key[ $key ] = $points->points;
			}
		);

		$save_button = sprintf(
			'
			<div class="tuja-buttons">
        		<button type="submit" class="button" name="tuja_action" value="%s">Spara</button>
    		</div>',
			self::ACTION_SAVE
		);

		include 'views/stations-points.php';
	}
}
