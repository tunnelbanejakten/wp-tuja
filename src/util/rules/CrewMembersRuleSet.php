<?php

namespace tuja\util\rules;


use DatePeriod;
use tuja\data\model\Competition;

class CrewMembersRuleSet extends RuleSet {

	function get_group_size_range() {
		return [ 0, 100 ];
	}

	function is_group_leader_required(): bool {
		return false;
	}

	function is_contact_information_required_for_regular_group_member(): bool {
		return true;
	}

	function is_adult_supervisor_required(): bool {
		return false;
	}

	public function get_create_registration_period( Competition $competition ): DatePeriod {
		return $this->up_until_days_before( $competition, 0 );
	}

	public function get_update_registration_period( Competition $competition ): DatePeriod {
		return $this->up_until_days_before( $competition, 0 );
	}

	public function get_delete_registration_period( Competition $competition ): DatePeriod {
		return $this->get_delete_group_member_period( $competition );
	}

	public function get_delete_group_member_period( Competition $competition ): DatePeriod {
		return $this->up_until_days_before( $competition, 14 );
	}

	function is_ssn_required(): bool {
		return false;
	}
}