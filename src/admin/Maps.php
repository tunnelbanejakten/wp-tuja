<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Map;
use tuja\data\model\Marker;
use tuja\data\model\Station;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\store\MapDao;
use tuja\data\store\CompetitionDao;
use tuja\data\model\ValidationException;
use tuja\data\store\MarkerDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\StationDao;

class Maps {
	const ACTION_NAME_DELETE_PREFIX = 'tuja_map_delete__';
	const FIELD_VALUE_SEP           = ' ';

	private $competition;

	public function __construct() {
		$db_competition     = new CompetitionDao();
		$this->question_dao = new QuestionDao();
		$this->map_dao      = new MapDao();
		$this->marker_dao   = new MarkerDao();
		$this->station_dao  = new StationDao();
		$this->competition  = $db_competition->get( $_GET['tuja_competition'] );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}

	private static function key( int $map_id, string $type, int $question_id, int $station_id ) {
		return join( '__', array( 'tuja_marker_raw', $map_id, $type, $question_id, $station_id ) );
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
				$new_map_id = $this->map_dao->create( $props );
				if ( $new_map_id !== false ) {
					AdminUtils::printSuccess(
						sprintf(
							'<span id="tuja_map_create_map_result" data-map-id="%s">Karta %s har lagts till.</span>',
							$new_map_id,
							$props->name
						)
					);
				}
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			}
		} elseif ( substr( $_POST['tuja_action'], 0, strlen( self::ACTION_NAME_DELETE_PREFIX ) ) == self::ACTION_NAME_DELETE_PREFIX ) {
			$map_id = intval( substr( $_POST['tuja_action'], strlen( self::ACTION_NAME_DELETE_PREFIX ) ) );
			try {
				$affected_rows = $this->map_dao->delete( $map_id );
				$success       = $affected_rows !== false && $affected_rows === 1;
				if ( $success ) {
					AdminUtils::printSuccess( 'Kartan togs bort.' );
				} else {
					AdminUtils::printError( 'Kunde inte ta bort kartan.' );
				}
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			}
		} elseif ( $_POST['tuja_action'] == 'save' ) {
			$markers = $this->get_markers();

			$maps = $this->map_dao->get_all_in_competition( $this->competition->id );

			$questions = $this->question_dao->get_all_in_competition( $this->competition->id );

			foreach ( $maps as $map ) {
				$user_value = @$_POST[ 'tuja_map_name__' . $map->id ];
				if ( $user_value !== $map->name ) {
					$map->name = $user_value;
					$this->map_dao->update( $map );
				}
			}

			$stations          = $this->station_dao->get_all_in_competition( $this->competition->id );
			$marker_props_list = array_merge(
				array( array( Marker::MARKER_TYPE_START, 0, 0 ) ),
				array_map(
					function ( AbstractQuestion $question ) {
						return array( Marker::MARKER_TYPE_TASK, $question->id, 0 );
					},
					$questions
				),
				array_map(
					function ( Station $station ) {
						return array( Marker::MARKER_TYPE_TASK, 0, $station->id );
					},
					$stations
				)
			);

			foreach ( $marker_props_list as $marker_props ) {
				list ($type, $question_id, $station_id) = $marker_props;
				foreach ( $maps as $map ) {
					$key = self::key( $map->id, $type, $question_id ?: 0, $station_id ?: 0 );
					// Value specified:
					// Marker exists:
						// Update marker
					// Marker DOES NOT exist:
						// Create marker.
					// Value NOT specified:
					// Marker exists:
						// Delete marker.
					// Marker DOES NOT exist:
						// Do nothing.
					$user_value = @$_POST[ $key ];
					if ( ! empty( $user_value ) ) {
						list ($gps_coord_lat, $gps_coord_long, $name) = explode( self::FIELD_VALUE_SEP, $user_value, 3 );
						if ( isset( $markers[ $key ] ) ) {
							$marker                 = $markers[ $key ];
							$marker->gps_coord_lat  = floatval( $gps_coord_lat );
							$marker->gps_coord_long = floatval( $gps_coord_long );
							$marker->name           = $name;

							try {
								$this->marker_dao->update( $marker );
							} catch ( Exception $e ) {
								AdminUtils::printException( $e );
							}
						} else {
							$marker                        = new Marker();
							$marker->map_id                = $map->id;
							$marker->type                  = $type;
							$marker->link_form_question_id = $question_id ?: null;
							$marker->link_station_id       = $station_id ?: null;
							$marker->gps_coord_lat         = floatval( $gps_coord_lat );
							$marker->gps_coord_long        = floatval( $gps_coord_long );
							$marker->name                  = $name;

							try {
								$this->marker_dao->create( $marker );
							} catch ( Exception $e ) {
								AdminUtils::printException( $e );
							}
						}
					} else {
						if ( isset( $markers[ $key ] ) ) {
							$marker = $markers[ $key ];

							try {
								$this->marker_dao->delete( $marker->id );
							} catch ( Exception $e ) {
								AdminUtils::printException( $e );
							}
						}
					}
				}
			}
		}
	}

	private function get_markers() {
		return array_reduce(
			$this->marker_dao->get_all_in_competition( $this->competition->id ),
			function ( $res, Marker $marker ) {
				$key         = self::key( $marker->map_id, $marker->type, $marker->link_form_question_id ?: 0, $marker->link_station_id ?: 0 );
				$res[ $key ] = $marker;
				return $res;
			},
			array()
		);
	}

	private static function get_field_value( string $key, array $markers ) {
		return @$markers[ $key ]
		? join(
			self::FIELD_VALUE_SEP,
			array(
				$markers[ $key ]->gps_coord_lat,
				$markers[ $key ]->gps_coord_long,
				$markers[ $key ]->name,
			)
		)
		: '';
	}

	private static function get_html_fields_values( array $keys, array $markers ) {
		return array_combine(
			$keys,
			array_map(
				function ( string $key ) use ( $markers ) {
					return self::get_field_value( $key, $markers );
				},
				$keys
			)
		);
	}

	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		$import_url = add_query_arg(
			array(
				'tuja_competition' => $this->competition->id,
				'tuja_view'        => 'MapsImport',
			)
		);

		$maps                  = $this->map_dao->get_all_in_competition( $competition->id );
		$questions             = $this->question_dao->get_all_in_competition( $this->competition->id );
		$markers               = $this->get_markers();
		$start_position_fields = self::get_html_fields_values(
			array_map(
				function ( Map $map ) {
					return self::key( $map->id, Marker::MARKER_TYPE_START, 0, 0 );
				},
				$maps
			),
			$markers
		);
		$questions_fields      = array_map(
			function ( AbstractQuestion $question ) use ( $maps, $markers ) {
				return array(
					'label'  => $question->text,
					'fields' => self::get_html_fields_values(
						array_map(
							function ( Map $map ) use ( $question ) {
								return self::key( $map->id, Marker::MARKER_TYPE_TASK, $question->id, 0 );
							},
							$maps
						),
						$markers
					),
				);
			},
			$questions
		);
		$stations              = $this->station_dao->get_all_in_competition( $this->competition->id );
		$stations_fields       = array_map(
			function ( Station $station ) use ( $maps, $markers ) {
				return array(
					'label'  => $station->name,
					'fields' => self::get_html_fields_values(
						array_map(
							function ( Map $map ) use ( $station ) {
								return self::key( $map->id, Marker::MARKER_TYPE_TASK, 0, $station->id );
							},
							$maps
						),
						$markers
					),
				);
			},
			$stations
		);

		include 'views/maps.php';
	}
}
