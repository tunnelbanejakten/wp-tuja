<?php

namespace tuja\data\store;

use tuja\data\model\Competition;
use tuja\util\Database;

class CompetitionDao extends AbstractDao {
	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'competition' );
	}

	function create( Competition $competition ) {
		$affected_rows = $this->wpdb->insert( $this->table,
			array(
				'random_id'            => $this->id->random_string(),
				'name'                 => $competition->name,
//				'create_group_start'                  => self::to_db_date($competition->create_group_start),
//				'create_group_end'                    => self::to_db_date($competition->create_group_end),
//				'edit_group_start'                    => self::to_db_date($competition->edit_group_start),
//				'edit_group_end'                      => self::to_db_date($competition->edit_group_end),
				'event_start'                         => self::to_db_date( $competition->event_start ),
				'event_end'                           => self::to_db_date( $competition->event_end ),
				'initial_group_status' => $competition->initial_group_status
			),
			array(
				'%s',
				'%s',
//				'%d',
//				'%d',
//				'%d',
//				'%d',
				'%d',
				'%d',
				'%s'
			) );
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	function update( Competition $competition ) {
		$competition->validate();

		return $this->wpdb->update( $this->table,
			array(
				'name'                 => $competition->name,
//				'create_group_start'                  => self::to_db_date($competition->create_group_start),
//				'create_group_end'                    => self::to_db_date($competition->create_group_end),
//				'edit_group_start'                    => self::to_db_date($competition->edit_group_start),
//				'edit_group_end'                      => self::to_db_date($competition->edit_group_end),
				'event_start'                         => self::to_db_date( $competition->event_start ),
				'event_end'                           => self::to_db_date( $competition->event_end ),
				'initial_group_status' => $competition->initial_group_status
			),
			array(
				'id' => $competition->id
			) );
	}

	function get( $id ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_competition( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE id = %d',
			$id );
	}

	function get_all() {
		return $this->get_objects(
			function ( $row ) {
				return self::to_competition( $row );
			},
			'SELECT * FROM ' . $this->table );
	}

	private static function to_competition( $result ): Competition {
		$c            = new Competition();
		$c->name      = $result->name;
		$c->id        = $result->id;
		$c->random_id = $result->random_id;
//		$c->create_group_start                     = self::from_db_date($result->create_group_start);
//		$c->create_group_end                       = self::from_db_date($result->create_group_end);
//		$c->edit_group_start                       = self::from_db_date($result->edit_group_start);
//		$c->edit_group_end                         = self::from_db_date($result->edit_group_end);
		$c->event_start                            = self::from_db_date( $result->event_start );
		$c->event_end                              = self::from_db_date( $result->event_end );
		$c->initial_group_status = $result->initial_group_status;

		return $c;
	}

	public function get_by_key( $competition_key ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_competition( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE random_id = %s',
			$competition_key );
	}

}