<?php

namespace tuja\util\rules;


use tuja\data\model\Competition;

class YoungParticipantsRuleSet extends RuleSet {

	function get_group_size_range() {
		return [ 4, 8 ];
	}

	function is_group_leader_required(): bool {
		return true;
	}

	function is_phone_only_required_for_group_leader(): bool {
		return true;
	}

	function is_adult_supervisor_required(): bool {
		return true;
	}

	function is_create_registration_allowed( Competition $competition ): bool {
		return $this->get_days_until( $competition ) >= 7;
	}

	function is_update_registration_allowed( Competition $competition ): bool {
		return $this->get_days_until( $competition ) >= 4;
	}

	function is_delete_registration_allowed( Competition $competition ): bool {
		return $this->get_days_until( $competition ) >= 4;
	}

	function is_delete_group_member_allowed( Competition $competition ): bool {
		return $this->is_delete_registration_allowed( $competition );
	}
}