<?php

namespace tuja\data\store;

use DateTime;
use tuja\data\model\Event;
use tuja\util\Database;

class EventDao extends AbstractDao {

	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'event' );
	}

	function create( Event $event ) {
		$event->validate();

		$affected_rows = $this->wpdb->insert(
			$this->table,
			array(
				'competition_id' => $event->competition_id,
				'created_at'     => self::to_db_date( new DateTime() ),
				'event_name'     => $event->event_name,
				'event_data'     => $event->event_data,
				'team_id'        => $event->group_id,
				'person_id'      => $event->person_id,
				'object_type'    => $event->object_type,
				'object_id'      => $event->object_id,
			),
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
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

	function get_by_group( $competition_id, $group_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_event( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE competition_id = %d AND team_id = %d ORDER BY id ASC',
			$competition_id,
			$group_id
		);
	}

	protected static function to_event( $result ): Event {
		$event                 = new Event();
		$event->id             = $result->id;
		$event->competition_id = $result->competition_id;
		$event->created_at     = self::from_db_date( $result->created_at );
		$event->event_name     = $result->event_name;
		$event->event_data     = $result->event_data;
		$event->group_id       = $result->team_id;
		$event->person_id      = $result->person_id;
		$event->object_type    = $result->object_type;
		$event->object_id      = $result->object_id;

		return $event;
	}
}
