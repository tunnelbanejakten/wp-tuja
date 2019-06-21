<?php

namespace tuja\util\rules;


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
}