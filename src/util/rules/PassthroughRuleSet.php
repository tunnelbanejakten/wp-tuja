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

	function is_contact_information_required_for_regular_group_member(): bool {
		return false;
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

	function is_ssn_required(): bool {
		return false;
	}

	function is_person_note_enabled(): bool {
		return true;
	}

	function is_group_note_enabled(): bool {
		return true;
	}

	function is_crew(): bool {
		return false;
	}
}