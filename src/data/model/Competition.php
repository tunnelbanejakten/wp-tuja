<?php

namespace tuja\data\model;

use DateTime;

class Competition {
	public $id;
	public $random_id;
	public $name;
	public $event_start;
	public $event_end;
	public $fee_calculator;
	public $payment_options = array();
	public $app_config      = array();

	public $initial_group_status;

	public function validate() {
		if ( strlen( trim( $this->name ) ) < 1 ) {
			throw new ValidationException( 'name', 'Namnet måste fyllas i.' );
		}
		if ( strlen( $this->name ) > 100 ) {
			throw new ValidationException( 'name', 'Namnet är för långt.' );
		}
		if ( $this->event_start !== null && $this->event_end !== null && $this->event_start->diff( $this->event_end )->invert == 1 ) {
			throw new ValidationException( 'edit_group_end', 'Tävlingen måste sluta efter att den börjar.' );
		}
		if ( $this->initial_group_status !== null && ! in_array( $this->initial_group_status, self::allowed_initial_statuses() ) ) {
			throw new ValidationException( 'initial_group_status', 'Nya grupper kan bara ha en av följande statusar: ' . join( ', ', self::allowed_initial_statuses() ) );
		}
	}

	public static function allowed_initial_statuses() {
		return array_merge( array( Group::DEFAULT_STATUS ), Group::STATUS_TRANSITIONS[ Group::DEFAULT_STATUS ] );
	}

	public function is_ongoing(): bool {
		$now              = new DateTime();
		$is_event_ongoing = ( $this->event_start == null || $this->event_start <= $now )
		                    && ( $this->event_end == null || $now <= $this->event_end );
		return $is_event_ongoing;
	}
}
