<?php

namespace tuja\util\rules;


use DateInterval;
use DatePeriod;
use DateTime;
use tuja\data\model\Competition;

abstract class RuleSet {
	abstract public function get_create_registration_period( Competition $competition ): DatePeriod;

	abstract public function get_update_registration_period( Competition $competition ): DatePeriod;

	abstract public function get_delete_registration_period( Competition $competition ): DatePeriod;

	abstract public function get_delete_group_member_period( Competition $competition ): DatePeriod;

	public function is_create_registration_allowed( Competition $competition ): bool {
		return $this->is_now( $this->get_create_registration_period( $competition ) );
	}

	public function is_update_registration_allowed( Competition $competition ): bool {
		return $this->is_now( $this->get_update_registration_period( $competition ) );
	}

	public function is_delete_registration_allowed( Competition $competition ): bool {
		return $this->is_now( $this->get_delete_registration_period( $competition ) );
	}

	public function is_delete_group_member_allowed( Competition $competition ): bool {
		return $this->is_now( $this->get_delete_group_member_period( $competition ) );
	}

	private function is_now( DatePeriod $period ) {
		$now = new DateTime();

		return $period->start < $now && $now < $period->end;
	}

	abstract function get_group_size_range();

	abstract function is_group_leader_required(): bool;

	abstract function is_contact_information_required_for_regular_group_member(): bool;

	abstract function is_adult_supervisor_required(): bool;

	protected function up_until_days_before( Competition $competition, $days_before ): DatePeriod {
		if ( ! isset( $competition->event_start ) ) {
			return $this->year_before_and_after_now();
		}
		$period = new DatePeriod(
			new DateTime( '@0' ),
			new DateInterval( 'P1D' ),
			( clone $competition->event_start )
				->setTime( 23, 59, 59 )
				->sub( new DateInterval( 'P' . ( $days_before ) . 'D' ) )
		);

		return $period;
	}

	protected function year_before_and_after_now(): DatePeriod {
		$now = new DateTime();

		return new DatePeriod(
			( clone $now )->setTime( 0, 0 )->sub( new DateInterval( 'P1Y' ) ),
			new DateInterval( 'P1D' ),
			( clone $now )->setTime( 0, 0 )->add( new DateInterval( 'P1Y' ) )
		);

	}
}