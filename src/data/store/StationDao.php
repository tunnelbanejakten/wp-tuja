<?php

namespace tuja\data\store;

use tuja\data\model\Station;
use tuja\util\Database;

class StationDao extends AbstractDao {

	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'station' );
	}

	function create( Station $station ) {
		$station->validate();

		$affected_rows = $this->wpdb->insert( $this->table,
			array(
				'random_id'               => $this->id->random_string(),
				'competition_id'          => $station->competition_id,
				'name'                    => $station->name,
				'location_gps_coord_lat'  => $station->location_gps_coord_lat,
				'location_gps_coord_long' => $station->location_gps_coord_long,
				'location_description'    => $station->location_description
			),
			array(
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s'
			) );
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	function update( Station $station ) {
		$station->validate();

		return $this->wpdb->update( $this->table,
			array(
				'name'                    => $station->name,
				'location_gps_coord_lat'  => $station->location_gps_coord_lat,
				'location_gps_coord_long' => $station->location_gps_coord_long,
				'location_description'    => $station->location_description
			),
			array(
				'id' => $station->id
			) );
	}

	function delete( $id ) {
		$query_template = 'DELETE FROM ' . $this->table . ' WHERE id = %d';

		return $this->wpdb->query( $this->wpdb->prepare( $query_template, $id ) );
	}

	function get( $id ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_station( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE id = %d',
			$id );
	}

	function get_all_in_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_station( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE competition_id = %d',
			$competition_id );
	}
}
