<?php

namespace tuja\data\store;

use tuja\data\model\Map;
use tuja\util\Database;

class MapDao extends AbstractDao {

	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'map' );
	}

	function create( Map $map ) {
		$map->validate();

		$affected_rows = $this->wpdb->insert(
			$this->table,
			array(
				'random_id'      => $this->id->random_string(),
				'competition_id' => $map->competition_id,
				'name'           => $map->name,
			),
			array(
				'%s',
				'%d',
				'%s',
			)
		);
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	function update( Map $map ) {
		$map->validate();

		return $this->wpdb->update(
			$this->table,
			array(
				'name' => $map->name,
			),
			array(
				'id' => $map->id,
			)
		);
	}

	function delete( $id ) {
		$query_template = 'DELETE FROM ' . $this->table . ' WHERE id = %d';

		return $this->wpdb->query( $this->wpdb->prepare( $query_template, $id ) );
	}

	function get( $id ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_map( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE id = %d',
			$id
		);
	}

	function get_all_in_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_map( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE competition_id = %d',
			$competition_id
		);
	}
}
