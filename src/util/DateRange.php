<?php


namespace tuja\util;


use DateTime;
use DateTimeInterface;

class DateRange {
	private $from;
	private $to;

	public function __construct( $from, $to ) {
		$this->from = $from;
		$this->to   = $to;
	}

	public function contains( DateTimeInterface $date_time ): bool {
		return ( $this->from === null || $this->from <= $date_time )
		       && ( $this->to === null || $date_time <= $this->to );
	}

	public function is_now(): bool {
		return $this->contains( new DateTime() );
	}

	public static function from( DateTimeInterface $from ): DateRange {
		return new DateRange( $from, null );
	}

	public static function until( DateTimeInterface $to ): DateRange {
		return new DateRange( null, $to );
	}

	public static function between( DateTimeInterface $from, DateTimeInterface $to ): DateRange {
		return new DateRange( $from, $to );
	}
}