<?php

namespace tuja\util\rules;


//use tuja\data\model\Group;

abstract class RuleSet {
	// TODO: Implement as part of https://github.com/tunnelbanejakten/wp-tuja/issues/96
//	abstract function is_create_registration_allowed();

	// TODO: Implement as part of https://github.com/tunnelbanejakten/wp-tuja/issues/96
//	abstract function is_update_registration_allowed( Group $group ): bool;

	// TODO: Implement as part of https://github.com/tunnelbanejakten/wp-tuja/issues/96
//	abstract function is_delete_registration_allowed( Group $group ): bool;

	abstract function get_group_size_range();

	abstract function is_group_leader_required(): bool;

	abstract function is_phone_only_required_for_group_leader(): bool;

	abstract function is_adult_supervisor_required(): bool;
}