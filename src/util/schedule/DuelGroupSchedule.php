<?php

namespace tuja\util\schedule;

use tuja\data\model\Competition;
use Exception;
use DateInterval;
use DateTime;
use DateTimeImmutable;

/**
 * Class for generating lists of timestamps (DateTime objects) according to these rules:
 *
 *    - All events are on whole hours.
 *      Example: 9 AM, 10 AM, 11 AM and so on.
 *
 *    - The start_hour_offset parameter defines how long after the competition starts that the first event will be set.
 *      If the competition doesn't start at minute 0 then we'll start counting from the next hour instead.
 *      Example: If the competition starts at 10:00 then the first event will be at 10 (since minute is 00).
 *      Example: If the competition starts at 09:01 then the first event will (also) be at 10 (since minute is not 00).
 *      Example: If the competition starts at 09:30 then the first event will (also) be at 10 (since minute is not 00).
 *
 *    - Certain hours of the day can be skipped/blocked.
 *
 * A more complete:
 *   Given:
 *    - Competition starts at 9:30
 *    - Competition ends at 17:00
 *    - start_hour_offset is 1
 *    - blocked_utc_hours is [12]
 *   Then generate(...) would return these dates:
 *    - 10:00 will NOT be returned since it's too early based on the "start hour offset"
 *    - 11:00
 *    - 12:00 will NOT be returned since it's in the "blocked list"
 *    - 13:00
 *    - 14:00
 *    - 15:00
 *    - 16:00
 *    - 17:00 will NOT be returned since competition has ended (or ends) by then
 */
class DuelGroupSchedule {
	public $start_hour_offset = 1;
	public $blocked_utc_hours = array();
	private $one_hour;

	public function __construct( int $start_hour_offset, array $blocked_utc_hours ) {
		$this->start_hour_offset = $start_hour_offset;
		$this->blocked_utc_hours = $blocked_utc_hours;
		$this->one_hour          = new DateInterval( 'PT1H' );
	}

	public function generate( Competition $competition ): array {
		if ( is_null( $competition->event_start ) || is_null( $competition->event_end ) ) {
			throw new Exception( 'Competition has no start and/or end time.' );
		}
		$first_event                      = DateTime::createFromImmutable( $competition->event_start );
		$competition_starts_on_whole_hour = $first_event->format( 'i' ) !== '00';
		$first_event_hour_offset          = $this->start_hour_offset + ( $competition_starts_on_whole_hour ? 1 : 0 );
		$first_event                      = $first_event->setTime( intval( $first_event->format( 'G' ) ) + $first_event_hour_offset, 0 );

		$result_events = array();
		$current_event = new DateTime( '@' . $first_event->getTimestamp() );
		while ( $current_event < $competition->event_end ) {
			$current_utc_hour = intval( $current_event->format( 'G' ) );
			$is_allowed_hour  = ! in_array( $current_utc_hour, $this->blocked_utc_hours, true );
			if ( $is_allowed_hour ) {
				$result_events[] = new DateTimeImmutable( '@' . $current_event->getTimestamp() );
			}
			$current_event->add( $this->one_hour ); // Advance time for next iteration.
		}

		return $result_events;
	}
}
