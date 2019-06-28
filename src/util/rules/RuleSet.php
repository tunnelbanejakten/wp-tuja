<?php

namespace tuja\util\rules;


use DateTime;
use tuja\data\model\Competition;

abstract class RuleSet {
	abstract function is_create_registration_allowed( Competition $competition ): bool;

	abstract function is_update_registration_allowed( Competition $competition ): bool;

	abstract function is_delete_registration_allowed( Competition $competition ): bool;

	abstract function is_delete_group_member_allowed( Competition $competition ): bool;

	abstract function get_group_size_range();

	abstract function is_group_leader_required(): bool;

	abstract function is_phone_only_required_for_group_leader(): bool;

	abstract function is_adult_supervisor_required(): bool;

	protected function get_days_until( Competition $competition ) {
		return isset( $competition->event_start ) ?
			$this->days_until( $competition->event_start ) :
			null;
	}

	//   1 = date is tomorrow
	//   0 = today
	//  -1 = date was yesterday
	private function days_until( DateTime $date ) {
		$diff = $date->diff( new DateTime(), false );

		return $diff->days * ( $diff->invert == 1 ? 1 : - 1 );
	}
}