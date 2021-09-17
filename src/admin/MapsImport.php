<?php

namespace tuja\admin;

use DOMDocument;
use SimpleXMLElement;
use Exception;
use tuja\data\model\Map;
use tuja\data\model\Marker;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\question\TextQuestion;
use tuja\data\store\MapDao;
use tuja\data\store\MarkerDao;
use tuja\data\store\CompetitionDao;
use tuja\data\model\ValidationException;
use tuja\data\store\QuestionDao;

use function PHPUnit\Framework\matches;

class MapsImport {
	const MAGIC_NUMBER_NO_LINKING = -42;

	private $competition;
	private $db_map;
	private $db_marker;

	public function __construct() {
		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $_GET['tuja_competition'] );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}

		$this->db_map        = new MapDao();
		$this->existing_maps = array_reduce(
			$this->db_map->get_all_in_competition( $this->competition->id ),
			function ( $res, Map $map ) {
				$res[ $map->name ] = $map;
				return $res;
			},
			array()
		);

		$this->db_marker        = new MarkerDao();
		$this->existing_markers = $this->db_marker->get_all_in_competition( $this->competition->id );
	}


	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $this->is_parse_file_mode() ) {

		} elseif ( $this->is_save_mode() ) {
		}
	}

	private function is_parse_file_mode():bool {
		return $_POST['tuja_action'] == 'map_import_parse';
	}

	private function is_save_mode():bool {
		return $_POST['tuja_action'] == 'map_import_save';
	}


	private function get_marker_questions() {
		$question_dao = new QuestionDao();
		$questions    = $question_dao->get_all_in_competition( $this->competition->id );

		$pseudo_question_no_linking = new TextQuestion( '(Koppla inte)', null, self::MAGIC_NUMBER_NO_LINKING );

		return array_merge( array( $pseudo_question_no_linking ), $questions );
	}

	private function get_or_create_map( $map_name ) {
		$matching_maps = array_values(
			array_filter(
				$this->existing_maps,
				function ( Map $map ) use ( $map_name ) {
					return $map->name == $map_name;
				}
			)
		);
		if ( count( $matching_maps ) === 1 ) {
			return $matching_maps[0]->id;
		} elseif ( count( $matching_maps ) === 0 ) {
			$props                 = new Map();
			$props->name           = $map_name;
			$props->competition_id = $this->competition->id;
			try {
				$id = $this->db_map->create( $props );
				if ( $id !== false ) {
					return $id;
				}
				throw new Exception( 'Could not create map.' );
			} catch ( ValidationException $e ) {
				throw new Exception( 'Could not create map. Validation error.' );
			}
		} else {
			throw new Exception( 'Could not find map. Too many candidates.' );
		}
	}

	private function get_or_create_marker( $map_id, $name, float $lat, float $long ) {
		$matching_markers = array_values(
			array_filter(
				$this->existing_markers,
				function ( Marker $marker ) use ( $map_id, $name, $lat, $long ) {
					$is_map_match   = $marker->map_id == $map_id;
					$is_name_match  = $marker->name == $name;
					$is_type_match  = $marker->type == Marker::MARKER_TYPE_TASK;
					$is_coord_match = $marker->gps_coord_lat === $lat && $marker->gps_coord_long === $long;
					return $is_map_match && $is_type_match && ( $is_name_match || $is_coord_match );
				}
			)
		);
		if ( count( $matching_markers ) === 1 ) {
			return $matching_markers[0]->id;
		} elseif ( count( $matching_markers ) === 0 ) {
			$props                 = new Marker();
			$props->map_id         = $map_id;
			$props->gps_coord_lat  = $lat;
			$props->gps_coord_long = $long;
			$props->type           = Marker::MARKER_TYPE_TASK;
			$props->name           = $name;
			try {
				$id = $this->db_marker->create( $props );
				if ( $id !== false ) {
					return $id;
				}
				throw new Exception( 'Could not create marker.' );
			} catch ( ValidationException $e ) {
				throw new Exception( 'Could not create marker. Validation error.' );
			}
		} else {
			throw new Exception( 'Could not find marker. Too many candidates.' );
		}
	}

	private function set_marker_question( int $marker_id, int $question_id ) {
		$marker = $this->db_marker->get( $marker_id );
		if ( $marker !== false ) {
			$marker->link_form_question_id = $question_id;
			$affected_rows                 = $this->db_marker->update( $marker );
			return $affected_rows <= 1; // No updated rows could just mean that the same KML file was imported twice in a row.
		} else {
			return false;
		}
	}

	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		if ( $this->is_parse_file_mode() || $this->is_save_mode() ) {
			$data    = $_POST['tuja_maps_import_raw'];
			$objects = array();

			$match_count = preg_match_all( '|<Placemark>.*?</Placemark>|', str_replace( "\n", '', $data ), $matches );
			foreach ( $matches[0] as $match ) {
				preg_match( '|.*<name>(.*?)\s(.*?)</name>.*<coordinates>(.*?)</coordinates>.*|', $match, $placemark_data );
				list(, $map_name, $marker_name, $coordinates) = array_map( 'trim', $placemark_data );
				list($lat, $long)                             = explode( ',', $coordinates );
				$objects[]                                    = array(
					'map_name'    => $map_name,
					'marker_name' => $marker_name,
					'lat'         => $lat,
					'long'        => $long,
				);
			}

			// Map list.
			$map_labels = array_reduce(
				$objects,
				function ( $res, $object ) {
					$key = $object['map_name'];
					if ( ! isset( $res[ $key ] ) ) {
						$res[ $key ] = 0;
					}
					$res[ $key ]++;
					return $res;
				},
				array()
			);
			ksort( $map_labels );

			// Marker list.
			$markers_labels = array_reduce(
				$objects,
				function ( $res, $object ) {
					$key = $object['marker_name'];
					if ( ! isset( $res[ $key ] ) ) {
						$res[ $key ] = 0;
					}
					$res[ $key ]++;
					return $res;
				},
				array()
			);
			ksort( $markers_labels );

			if ( $this->is_save_mode() ) {
				foreach ( array_keys( $markers_labels ) as $marker ) {
					$target_question_id = intval( $_POST[ 'tuja_mapsimport_markerlabel__' . crc32( $marker ) . '__question' ] );
					if ( $target_question_id !== self::MAGIC_NUMBER_NO_LINKING && $target_question_id > 0 ) {
						foreach ( $objects as $object ) {
							if ( $object['marker_name'] == $marker ) {
								$message = sprintf(
									'Kartnål "%s" (%s, %s) på karta "%s" ska koppas till fråga %s. ',
									$object['marker_name'],
									$object['lat'],
									$object['long'],
									$object['map_name'],
									$target_question_id
								);
								try {
									// Does the map exist? Search by name.
									$map_id = $this->get_or_create_map( $object['map_name'] );
									// Does the marker exist? Search by name and by marker (find markers with same name and/or same coordinates).
									$marker_id = $this->get_or_create_marker(
										$map_id,
										$object['marker_name'],
										floatval( $object['lat'] ),
										floatval( $object['long'] )
									);

									$question_id       = intval( $target_question_id );
									$is_marker_updated = $this->set_marker_question( $marker_id, $question_id );
									if ( $is_marker_updated ) {
										AdminUtils::printSuccess( $message . 'Ok. Map: ' . $map_id . ' Marker: ' . $marker_id . ' Question: ' . $question_id );
									} else {
										AdminUtils::printError( $message . 'Failed to link question. Map: ' . $map_id . ' Marker: ' . $marker_id . ' Question: ' . $question_id );
									}
								} catch ( Exception $e ) {
									AdminUtils::printError( $message . $e->getMessage() );
								}
							}
						}
					}
				}
			}
		}

		$questions = $this->get_marker_questions();

		$existing_maps = $this->existing_maps;

		$back_url = add_query_arg(
			array(
				'tuja_competition' => $this->competition->id,
				'tuja_view'        => 'Maps',
			)
		);

		include 'views/maps-import.php';
	}
}
