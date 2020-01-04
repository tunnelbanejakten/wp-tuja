<?php

namespace tuja\frontend;


use Exception;
use tuja\frontend\router\GroupEditorInitiator;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\frontend\router\GroupPeopleEditorInitiator;
use tuja\util\rules\RuleResult;

class GroupStatus extends AbstractGroupView {
	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Status för %s' );
	}

	function output() {
		try {
			$group = $this->get_group();

			$rule_result = $group->evaluate_registration();

			$blockers = array_filter( $rule_result, function ( RuleResult $rule_result ) {
				return $rule_result->status == RuleResult::BLOCKER;
			} );

			$warnings = array_filter( $rule_result, function ( RuleResult $rule_result ) {
				return $rule_result->status == RuleResult::WARNING;
			} );

			$edit_group_link  = GroupEditorInitiator::link( $group );
			$edit_people_link = GroupPeopleEditorInitiator::link( $group );
			$home_link = GroupHomeInitiator::link( $group );
			include( 'views/group-status.php' );
		} catch ( Exception $e ) {
			print $this->get_exception_message_html( $e );
		}
	}
}