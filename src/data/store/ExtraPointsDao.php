<?php

namespace tuja\data\store;

use tuja\data\model\Points;
use tuja\util\Database;

class ExtraPointsDao extends AbstractPointsDao {

	function __construct() {
		parent::__construct(
			Database::get_table( 'extra_points' ),
			'name'
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
			'INNER JOIN ' . Database::get_table( 'team' ) . ' t ON p.team_id = t.id ' .
			'WHERE t.competition_id = %d',
			$competition_id
		);
	}

	public function all_names( $competition_id ): array {
		$existing_points = $this->get_by_competition( $competition_id );

		return array_unique(
			array_map(
				function ( Points $points ) {
					return $points->name;
				},
				$existing_points
			)
		);
	}

}
