<?php

namespace tuja\data\store;

use DateTime;
use DateTimeZone;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\Message;
use tuja\data\model\Person;
use tuja\util\Id;
use tuja\util\Phone;

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

	// TODO: Move all to_* methods to the corresponding model classes. Already done for FormDao, CompetitionDao and QuestionDao.
	protected static function to_group_category( $result ): GroupCategory {
		$gc                 = new GroupCategory();
		$gc->id             = $result->id;
		$gc->competition_id = $result->competition_id;
		$gc->is_crew        = $result->is_crew != 0;
		$gc->name           = $result->name;

		return $gc;
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
			return new DateTime( '@' . $dbDate, new DateTimeZone( 'UTC' ) );
		} else {
			return null;
		}
	}

}