<?php

namespace tuja\util\rules;


use DatePeriod;
use tuja\data\model\Competition;

class PassthroughRuleSet extends RuleSet {

	function get_group_size_range() {
		return [ 0, 10000 ];
	}

	function is_group_leader_required(): bool {
		return false;
	}

	function is_phone_only_required_for_group_leader(): bool {
		return true;
	}

	function is_adult_supervisor_required(): bool {
		return false;
	}

	public function get_create_registration_period( Competition $competition ): DatePeriod {
		return $this->year_before_and_after_now();
	}

	public function get_update_registration_period( Competition $competition ): DatePeriod {
		return $this->year_before_and_after_now();
	}

	public function get_delete_registration_period( Competition $competition ): DatePeriod {
		return $this->year_before_and_after_now();
	}

	public function get_delete_group_member_period( Competition $competition ): DatePeriod {
		return $this->year_before_and_after_now();
	}
}