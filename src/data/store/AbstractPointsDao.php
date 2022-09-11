<?php

namespace tuja\data\store;

use DateTime;
use tuja\data\model\Points;
use tuja\data\model\Event;

abstract class AbstractPointsDao extends AbstractDao {

	function __construct( $table, $id_column ) {
		parent::__construct();
		$this->table     = $table;
		$this->id_column = $id_column;
		$this->event_dao = new EventDao();
		$this->group_dao = new GroupDao();
	}

	// TODO: Handle problems in case replace(...) or delete(...) fail.
	public function set( $group_id, $object_id, $points = null ) {
		if ( isset( $points ) ) {
			$affected = $this->wpdb->replace(
				$this->table,
				array(
					$this->id_column => $object_id,
					'team_id'        => $group_id,
					'points'         => $points,
					'created_at'     => self::to_db_date( new DateTime() ),
				),
				array(
					is_numeric( $object_id ) ? '%d' : '%s',
					'%d',
					'%d',
					'%d',
				)
			);
		} else {
			$affected = $this->wpdb->delete(
				$this->table,
				array(
					$this->id_column => $object_id,
					'team_id'        => $group_id,
				),
				array(
					is_numeric( $object_id ) ? '%d' : '%s',
					'%d',
				)
			);
		}
		if ( $affected > 0 ) {
			$group = $this->group_dao->get( $group_id );

			$event                 = new Event();
			$event->competition_id = $group->competition_id;
			$event->event_name     = Event::EVENT_SET_POINTS;
			$event->event_data     = json_encode(
				array(
					'remote_addr'          => filter_var( @$_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP ),
					'http_x_forwarded_for' => filter_var( @$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP ),
					'request_uri'          => $_SERVER['REQUEST_URI'],
					'points'               => $points,
				)
			);
			$event->group_id       = $group->id;
			// $event->person_id;
			$event->object_type = strtoupper( $this->table );
			$event->object_id   = $object_id;
			$this->event_dao->create( $event );
		}
	}

	public function get_by_group( $group_id ) {
		return $this->get_objects(
			function ( $row ) {
				return $this->to_points( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE team_id = %d',
			$group_id
		);
	}

	protected function to_points( $result ): Points {
		$p                     = new Points();
		$id_column_value       = $result->{$this->id_column};
		$p->{$this->id_column} = is_numeric( $id_column_value ) ? intval( $id_column_value ) : $id_column_value;
		$p->group_id           = $result->team_id;
		$p->points             = intval( $result->points );
		$p->created            = self::from_db_date( $result->created_at );

		return $p;
	}

}
