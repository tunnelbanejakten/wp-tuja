<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Map;
use tuja\data\model\Marker;
use tuja\data\store\MapDao;
use tuja\data\store\CompetitionDao;
use tuja\data\model\ValidationException;
use tuja\data\store\MarkerDao;
use tuja\data\store\QuestionDao;

class Maps {

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

		if ( $_POST['tuja_action'] == 'map_create' ) {
			$props                 = new Map();
			$props->name           = $_POST['tuja_map_name'];
			$props->competition_id = $this->competition->id;
			try {
				$station_dao = new MapDao();
				$station_dao->create( $props );
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			}
		} elseif ( $_POST['tuja_action'] == 'save' ) {
			$marker_dao = new MarkerDao();
			$markers    = $this->get_markers();

			$map_dao = new MapDao();
			$maps    = $map_dao->get_all_in_competition( $this->competition->id );

			$question_dao = new QuestionDao();
			$questions    = $question_dao->get_all_in_competition( $this->competition->id );

			foreach ( $questions as $question ) {
				foreach ( $maps as $map ) {
					$key = sprintf( '%s__%s', $map->id, $question->id );
					// Value specified:
						// Marker exists:
							// Update marker
						// Marker DOES NOT exist:
							// Create marker
					// Value NOT specified:
						// Marker exists:
							// Delete marker
						// Marker DOES NOT exist:
							// Do nothing.
					$user_value = @$_POST[ 'tuja_marker_raw__' . $key ];
					if ( ! empty( $user_value ) ) {
						list ($gps_coord_lat, $gps_coord_long, $name) = explode( ' ', $user_value, 3 );
						if ( isset( $markers[ $key ] ) ) {
							$marker                 = $markers[ $key ];
							$marker->gps_coord_lat  = $gps_coord_lat;
							$marker->gps_coord_long = $gps_coord_long;
							$marker->name           = $name;

							try {
								$marker_dao->update( $marker );
							} catch(Exception $e) {
								AdminUtils::printException($e);
							}
						} else {
							$marker                        = new Marker();
							$marker->map_id                = $map->id;
							$marker->gps_coord_lat         = floatval( $gps_coord_lat );
							$marker->gps_coord_long        = floatval( $gps_coord_long );
							$marker->type                  = Marker::MARKER_TYPE_TASK;
							$marker->name                  = $name;
							$marker->link_form_question_id = $question->id;
							
							try {
								$marker_dao->create( $marker );
							} catch(Exception $e) {
								AdminUtils::printException($e);
							}
						}
					} else {
						if ( isset( $markers[ $key ] ) ) {
							$marker = $markers[ $key ];

							try {
								$marker_dao->delete( $marker->id );
							} catch(Exception $e) {
								AdminUtils::printException($e);
							}
						}
					}
				}
			}
		}
	}

	private function get_markers() {
		return array_reduce(
			( new MarkerDao() )->get_all_in_competition( $this->competition->id ),
			function ( $res, Marker $marker ) {
				$key         = sprintf( '%s__%s', $marker->map_id, $marker->link_form_question_id );
				$res[ $key ] = $marker;
				return $res;
			}
		);
	}


	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		$map_dao = new MapDao();
		$maps    = $map_dao->get_all_in_competition( $competition->id );

		$markers = $this->get_markers();

		$question_dao = new QuestionDao();
		$questions    = $question_dao->get_all_in_competition( $competition->id );

		include 'views/maps.php';
	}
}
