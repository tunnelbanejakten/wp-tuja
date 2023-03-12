<?php

namespace tuja\data\store;

use tuja\data\model\Marker;
use tuja\util\Database;

class MarkerDao extends AbstractDao {
	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'marker' );
	}

	function create( Marker $marker ) {
		$marker->validate();

		$affected_rows = $this->wpdb->insert(
			$this->table,
			array(
				'random_id'              => $this->id->random_string(),
				'map_id'                 => $marker->map_id,
				'gps_coord_lat'          => $marker->gps_coord_lat,
				'gps_coord_long'         => $marker->gps_coord_long,
				'type'                   => $marker->type,
				'name'                   => $marker->name,
				'description'            => $marker->description,
				'link_form_id'           => $marker->link_form_id,
				'link_form_question_id'  => $marker->link_form_question_id,
				'link_question_group_id' => $marker->link_question_group_id,
				'link_station_id'        => $marker->link_station_id,
			),
			array(
				'%s',
				'%d',
				'%f',
				'%f',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%d',
			)
		);
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	function delete( $id ) {
		$query_template = 'DELETE FROM ' . $this->table . ' WHERE id = %d';

		return $this->wpdb->query( $this->wpdb->prepare( $query_template, $id ) );
	}

	function update( Marker $marker ) {
		$marker->validate();

		return $this->wpdb->update(
			$this->table,
			array(
				'gps_coord_lat'          => $marker->gps_coord_lat,
				'gps_coord_long'         => $marker->gps_coord_long,
				'type'                   => $marker->type,
				'name'                   => $marker->name,
				'description'            => $marker->description,
				'link_form_id'           => $marker->link_form_id,
				'link_form_question_id'  => $marker->link_form_question_id,
				'link_question_group_id' => $marker->link_question_group_id,
				'link_station_id'        => $marker->link_station_id,
			),
			array(
				'id' => $marker->id,
			)
		);
	}

	function get( $id ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_marker( $row );
			},
			'
                SELECT
					m.*,
					dg.id AS link_duel_group_id,
					dg.name AS link_duel_group_name
                FROM ' . $this->table . ' AS m
				LEFT JOIN ' . Database::get_table( 'duel_group' ) . ' AS dg ON dg.link_form_question_id = m.link_form_question_id
                WHERE m.id = %d',
			$id
		);
	}

	function get_all_on_map( $map_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_marker( $row );
			},
			'
                SELECT
					m.*,
					dg.id AS link_duel_group_id,
					dg.name AS link_duel_group_name
                FROM ' . $this->table . ' AS m
				LEFT JOIN ' . Database::get_table( 'duel_group' ) . ' AS dg ON dg.link_form_question_id = m.link_form_question_id
                WHERE m.map_id = %d',
			$map_id
		);
	}

	function get_all_in_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_marker( $row );
			},
			'
                SELECT
					m.*,
					dg.id AS link_duel_group_id,
					dg.name AS link_duel_group_name
                FROM ' . $this->table . ' AS m 
					INNER JOIN ' . Database::get_table( 'map' ) . ' AS map ON m.map_id = map.id
					LEFT JOIN ' . Database::get_table( 'duel_group' ) . ' AS dg ON dg.link_form_question_id = m.link_form_question_id
                WHERE map.competition_id = %d',
			$competition_id
		);
	}

	private static function to_marker( $result ): Marker {
		$marker                         = new Marker();
		$marker->id                     = intval( $result->id );
		$marker->random_id              = $result->random_id;
		$marker->map_id                 = intval( $result->map_id );
		$marker->gps_coord_lat          = isset( $result->gps_coord_lat ) ? floatval( $result->gps_coord_lat ) : null;
		$marker->gps_coord_long         = isset( $result->gps_coord_long ) ? floatval( $result->gps_coord_long ) : null;
		$marker->type                   = $result->type;
		$marker->name                   = $result->name;
		$marker->description            = $result->description;
		$marker->link_form_id           = isset( $result->link_form_id ) ? intval( $result->link_form_id ) : null;
		$marker->link_form_question_id  = isset( $result->link_form_question_id ) ? intval( $result->link_form_question_id ) : null;
		$marker->link_question_group_id = isset( $result->link_question_group_id ) ? intval( $result->link_question_group_id ) : null;
		$marker->link_station_id        = isset( $result->link_station_id ) ? intval( $result->link_station_id ) : null;
		$marker->link_duel_group_id     = isset( $result->link_duel_group_id ) ? intval( $result->link_duel_group_id ) : null;
		$marker->link_duel_group_name   = $result->link_duel_group_name;

		return $marker;
	}

}
