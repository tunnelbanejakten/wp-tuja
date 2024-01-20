<?php

namespace tuja\data\model;

use DateTime;

class Form {

	public $id;
	public $random_id;
	public $name;
	public $competition_id;
	public $allow_multiple_responses_per_group;
	public $submit_response_start;
	public $submit_response_start_effective; // Read-only. Returns submit_response_start on form or event_start on competition.
	public $submit_response_end;
	public $submit_response_end_effective; // Read-only. Returns submit_response_end on form or event_end on competition.

	public function validate() {
		if ( strlen( trim( $this->name ) ) < 1 ) {
			throw new ValidationException( 'name', 'Namnet måste fyllas i.' );
		}
		if ( $this->submit_response_start !== null && $this->submit_response_end !== null && $this->submit_response_start->diff( $this->submit_response_end )->invert == 1 ) {
			throw new ValidationException( 'submit_response_end', 'Perioden måste sluta efter att den börjar.' );
		}
	}

	public function is_submit_allowed(): bool {
		return $this->is_opened() && ! $this->is_closed();
	}

	public function is_opened(): bool {
		$now  = ( new DateTime() )->getTimestamp();
		$open = $this->submit_response_start_effective == null || $now >= $this->submit_response_start_effective->getTimestamp();
		return $open;
	}

	public function is_closed(): bool {
		$now    = ( new DateTime() )->getTimestamp();
		$closed = $this->submit_response_end_effective != null && $now > $this->submit_response_end_effective->getTimestamp();
		return $closed;
	}
}
