<?php

namespace tuja\util\rules;


use tuja\data\model\Competition;

class CrewMembersRuleSet extends RuleSet {

	function get_group_size_range() {
		return [ 0, 100 ];
	}

	function is_group_leader_required(): bool {
		return false;
	}

	function is_phone_only_required_for_group_leader(): bool {
		return false;
	}

	function is_adult_supervisor_required(): bool {
		return false;
	}

	function is_create_registration_allowed( Competition $competition ): bool {
		return $this->get_days_until( $competition ) >= 0;
	}

	function is_update_registration_allowed( Competition $competition ): bool {
		return $this->get_days_until( $competition ) >= 0;
	}

	function is_delete_registration_allowed( Competition $competition ): bool {
		return $this->is_delete_group_member_allowed( $competition );
	}

	function is_delete_group_member_allowed( Competition $competition ): bool {
		return $this->get_days_until( $competition ) >= 14;
	}
}