<?php

namespace tuja\data\store;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use tuja\util\Id;

class AbstractDao {
	protected $id;
	protected $wpdb;
	protected $table;

	function __construct() {
		global $wpdb;
		$this->id   = new Id();
		$this->wpdb = $wpdb;
	}

	protected function get_object( $mapper, $query, ...$arguments ) {
		$db_results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $arguments ), OBJECT );
		if ( $db_results !== false && count( $db_results ) > 0 ) {
			return $mapper( $db_results[0] );
		}

		return false;
	}

	protected function get_objects( $mapper, $query, ...$arguments ) {
		$db_results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $arguments ), OBJECT );
		$results    = [];
		foreach ( $db_results as $result ) {
			$results[] = $mapper( $result );
		}

		return $results;
	}

	protected static function to_db_date( DateTime $dateTime = null ) {
		if ( $dateTime != null ) {
			return $dateTime->getTimestamp(); // Unix timestamps are always UTC
		} else {
			return null;
		}
	}

	protected static function from_db_date( $dbDate ) {
		if ( ! empty( $dbDate ) ) {
			return new DateTimeImmutable( '@' . $dbDate, new DateTimeZone( 'UTC' ) );
		} else {
			return null;
		}
	}

}