<?php


namespace tuja\util;


use DateTime;
use DateTimeImmutable;

class DateRange {
	private $from;
	private $to;

	public function __construct( $from, $to ) {
		$this->from = $from;
		$this->to   = $to;
	}

	public function contains( DateTime $date_time ): bool {
		return ( $this->from === null || $this->from <= $date_time )
		       && ( $this->to === null || $date_time <= $this->to );
	}

	public function is_now(): bool {
		return $this->contains( new DateTime() );
	}

	public static function from( DateTime $from ): DateRange {
		return new DateRange( $from, null );
	}

	public static function until( DateTime $to ): DateRange {
		return new DateRange( null, $to );
	}

	public static function between( DateTime $from, DateTime $to ): DateRange {
		return new DateRange( $from, $to );
	}
}