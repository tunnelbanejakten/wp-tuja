<?php

namespace tuja\data\store;

use tuja\util\Database;

class StationPointsDao extends AbstractPointsDao {

	function __construct() {
		parent::__construct(
			Database::get_table( 'station_points' ),
			'station_id'
		);
	}

	public function get_by_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return $this->to_points( $row );
			},
			'' .
			'SELECT p.* ' .
			'FROM ' . $this->table . ' p ' .
			'INNER JOIN ' . Database::get_table( 'station' ) . ' s ON p.station_id = s.id ' .
			'WHERE s.competition_id = %d',
			$competition_id
		);
	}
}
