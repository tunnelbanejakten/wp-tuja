<?php

namespace tuja\admin;

use Exception;
use tuja\admin\Maps;
use tuja\data\model\Marker;
use tuja\data\model\Station;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\model\ValidationException;
use tuja\data\store\MarkerDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\StationDao;
use tuja\util\FormUtils;

class Map extends Maps {
	const ACTION_NAME_SAVE   = 'map_save';
	const ACTION_NAME_DELETE = 'map_delete';
	const FIELD_VALUE_SEP    = '__';

	const FIELD_SUFFIX_LAT  = self::FIELD_VALUE_SEP . 'lat';
	const FIELD_SUFFIX_LONG = self::FIELD_VALUE_SEP . 'long';
	const FIELD_SUFFIX_NAME = self::FIELD_VALUE_SEP . 'name';

	public function __construct() {
		parent::__construct();
		$this->question_group_dao = new QuestionGroupDao();
		$this->question_dao       = new QuestionDao();
		$this->marker_dao         = new MarkerDao();
		$this->station_dao        = new StationDao();

		$this->map = $this->map_dao->get( $_GET['tuja_map'] );
		$this->assert_set( 'Could not find map', $this->map );
		$this->assert_same( 'Map is from different competition', $this->competition->id, $this->map->competition_id );
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		$maps_current = null;
		$maps_links   = array();
		$dao          = $this->map_dao;
		$maps         = $dao->get_all_in_competition( $this->competition->id );
		foreach ( $maps as $map ) {
			$active = $map->id === $this->map->id;
			if ( $active ) {
				$maps_current = $map->name;
			}
			$link         = add_query_arg(
				array(
					'tuja_competition' => $this->competition->id,
					'tuja_view'        => 'Map',
					'tuja_map'         => $map->id,
				)
			);
			$maps_links[] = BreadcrumbsMenu::item( $map->name, $link, $active );
		}

		return $menu->add(
			BreadcrumbsMenu::item( $maps_current ),
			...$maps_links,
		);
	}

	public function get_scripts(): array {
		return array(
			'admin-map.js',
			'leaflet-1.8.0.js',
		);
	}

	private static function key( string $type, int $question_id, int $station_id ) {
		return join( self::FIELD_VALUE_SEP, array( 'tuja_marker_raw', $type, $question_id, $station_id ) );
	}

	private function get_markers() {
		return array_reduce(
			$this->marker_dao->get_all_on_map( $this->map->id ),
			function ( $res, Marker $marker ) {
				$key         = self::key( $marker->type, $marker->link_form_question_id ?: 0, $marker->link_station_id ?: 0 );
				$res[ $key ] = $marker;
				return $res;
			},
			array()
		);
	}

	private static function get_field_values( string $key, array $markers ) {
		return array(
			'lat'  => array(
				$key . self::FIELD_SUFFIX_LAT,
				isset( $markers[ $key ] ) ? $markers[ $key ]->gps_coord_lat : '',
			),
			'long' => array(
				$key . self::FIELD_SUFFIX_LONG,
				isset( $markers[ $key ] ) ? $markers[ $key ]->gps_coord_long : '',
			),
			'name' => array(
				$key . self::FIELD_SUFFIX_NAME,
				isset( $markers[ $key ] ) ? $markers[ $key ]->name : '',
			),
		);
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return true;
		}

		if ( $_POST['tuja_action'] == self::ACTION_NAME_SAVE ) {
			$markers = $this->get_markers();

			$questions = $this->question_dao->get_all_in_competition( $this->competition->id );

			$map = $this->map;

			$user_value = @$_POST['tuja_map_name'];
			if ( $user_value !== $map->name ) {
				$map->name = $user_value;
				$this->map_dao->update( $map );
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
				$key                                    = self::key( $type, $question_id ?: 0, $station_id ?: 0 );
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
				$gps_coord_lat  = $_POST[ $key . self::FIELD_SUFFIX_LAT ];
				$gps_coord_long = $_POST[ $key . self::FIELD_SUFFIX_LONG ];
				$name           = $_POST[ $key . self::FIELD_SUFFIX_NAME ];
				if ( ! empty( $name ) ) {
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
						$marker->link_form_question_id = is_numeric( $question_id ) && $question_id > 0 ? intval( $question_id ) : null;
						$marker->link_station_id       = is_numeric( $station_id ) && $station_id > 0 ? intval( $station_id ) : null;
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
		} elseif ( $_POST['tuja_action'] === self::ACTION_NAME_DELETE ) {
			try {
				$affected_rows = $this->map_dao->delete( $this->map->id );
				$success       = $affected_rows !== false && $affected_rows === 1;
				if ( $success ) {
					$url = add_query_arg(
						array(
							'tuja_view' => 'Maps',
						)
					);

					AdminUtils::printSuccess( sprintf( 'Kartan har tagits bort. Vad sägs om att gå till <a href="%s" id="tuja_maps_link">kartlistan</a> för att kanske skapa en ny?', $url ) );
						return false;
				} else {
					AdminUtils::printError( 'Kunde inte ta bort kartan.' );
				}
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			}
		}
		return true;
	}

	public function output() {
		$is_map_available = $this->handle_post();

		if ( ! $is_map_available ) {
			return;
		}

		$competition     = $this->competition;
		$map             = $this->map;
		$markers         = $this->get_markers();
		$questions       = $this->question_dao->get_all_in_competition( $this->competition->id );
		$question_groups = array_reduce(
			$this->question_group_dao->get_all_in_competition( $this->competition->id ),
			function ( array $res, QuestionGroup $qg ) {
				$res[ $qg->id ] = $qg->text;
				return $res;
			},
			array()
		);

		//
		// Start marker
		//

		$start_field_key = self::key( Marker::MARKER_TYPE_START, 0, 0 );
		$start_field     = array(
			array(
				'label'          => 'Startplats',
				'short_label'    => 'Startplatsen',
				'question_group' => 'Startplats',
				'fields'         => self::get_field_values( $start_field_key, $markers ),
			),
		);

		//
		// Question markers
		//

		$questions_fields = array_map(
			function ( AbstractQuestion $question ) use ( $markers, $question_groups ) {
				$question_field_key = self::key( Marker::MARKER_TYPE_TASK, $question->id, 0 );
				$question_text_html = FormUtils::render_text_body( $question->text );
				return array(
					'label'          => $question_text_html,
					'short_label'    => sprintf( 'Fråga %s', $question->name ),
					'question_group' => $question_groups[ $question->question_group_id ] ?? sprintf( 'Namnlös grupp %s', $question->question_group_id ),
					'fields'         => self::get_field_values( $question_field_key, $markers ),
				);
			},
			$questions
		);
		usort(
			$questions_fields,
			function ( $questions_field_1, $questions_field_2 ) {
				return strcmp( $questions_field_1['question_group'], $questions_field_2['question_group'] );
			}
		);

		//
		// Station markers
		//

		$stations        = $this->station_dao->get_all_in_competition( $this->competition->id );
		$stations_fields = array_map(
			function ( Station $station ) use ( $markers ) {
				$station_field_key = self::key( Marker::MARKER_TYPE_TASK, 0, $station->id );
				return array(
					'label'          => $station->name,
					'short_label'    => sprintf( 'Station %s', $station->name ),
					'question_group' => 'Stationer',
					'fields'         => self::get_field_values( $station_field_key, $markers ),
				);
			},
			$stations
		);

		$marker_config = array_merge(
			$start_field,
			$questions_fields,
			$stations_fields
		);

		include 'views/map.php';
	}
}
