<?php

namespace tuja\data\store;

use DateTime;
use tuja\data\model\Points;
use tuja\util\Database;

class PointsDao extends AbstractDao
{
	function __construct() {
		parent::__construct();
		$this->table = Database::get_table('form_question_points');
	}

	// TODO: Handle problems in case replace(...) or delete(...) fail.
	function set( $group_id, $question_id, $points = null ) {
		if ( isset( $points ) ) {
			$this->wpdb->replace( $this->table,
				array(
					'form_question_id' => $question_id,
					'team_id'          => $group_id,
					'points'           => $points,
					'created_at'       => self::to_db_date( new DateTime() )
				),
				array(
					'%d',
					'%d',
					'%d',
					'%d'
				) );
		} else {
			$this->wpdb->delete( $this->table,
				array(
					'form_question_id' => $question_id,
					'team_id'          => $group_id
				),
				array(
					'%d',
					'%d'
				) );
		}
	}

	function get_by_group( $group_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_points( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE team_id = %d',
			$group_id );
	}

	function get_by_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_points( $row );
			},
			'' .
			'SELECT p.* ' .
			'FROM ' . $this->table . ' p ' .
			'  INNER JOIN ' . Database::get_table( 'form_question' ) . ' q ON p.form_question_id = q.id ' .
			'  INNER JOIN ' . Database::get_table( 'form' ) . ' f ON q.form_id = f.id ' .
			'WHERE f.competition_id = %d',
			$competition_id );
	}

	protected static function to_points( $result ): Points {
		$p                   = new Points();
		$p->form_question_id = $result->form_question_id;
		$p->group_id         = $result->team_id;
		$p->points           = $result->points;
		$p->created          = self::from_db_date( $result->created_at );

		return $p;
	}

}