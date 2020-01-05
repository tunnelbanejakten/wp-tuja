<?php

namespace tuja\data\store;

use tuja\data\model\ValidationException;
use tuja\util\Database;

class StringsDao extends AbstractDao {

	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'string' );
	}

	function set_all( int $competition_id, array $strings ) {
		$max_key_length = max( array_map( function ( string $key ) {
			return strlen( $key );
		}, array_keys( $strings ) ) );
		if ( $max_key_length > 100 ) {
			throw new ValidationException( null, 'String keys must be shorter than 100 characters.' );
		}

		$this->wpdb->delete( $this->table,
			array(
				'competition_id' => $competition_id,
			),
			array(
				'%d'
			) );

		foreach ( $strings as $key => $value ) {
			$this->wpdb->insert( $this->table,
				array(
					'competition_id' => $competition_id,
					'name'           => $key,
					'value'          => $value
				),
				array(
					'%d',
					'%s',
					'%s'
				) );
		}
	}

	function get_all( int $competition_id ) {
		$db_results = $this->wpdb->get_results( $this->wpdb->prepare( 'SELECT * FROM ' . $this->table . ' WHERE competition_id = %d', $competition_id ), OBJECT );
		$results    = [];
		foreach ( $db_results as $result ) {
			$results[ $result->name ] = $result->value;
		}

		return $results;
	}
}
