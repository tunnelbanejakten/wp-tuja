<?php

namespace tuja\util\rules;


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
}