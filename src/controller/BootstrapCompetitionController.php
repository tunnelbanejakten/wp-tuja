<?php
namespace tuja\controller;

use Exception;
use tuja\data\model\Competition;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\util\rules\CrewMembersRuleSet;
use tuja\util\rules\GroupCategoryRules;
use tuja\util\rules\OlderParticipantsRuleSet;
use tuja\util\rules\PassthroughRuleSet;
use tuja\util\rules\YoungParticipantsRuleSet;

class BootstrapCompetitionController {
	private $competition_dao;

	function __construct() {
		$this->competition_dao = new CompetitionDao();
		$this->category_dao    = new GroupCategoryDao();
		$this->group_dao       = new GroupDao();
	}

	function bootstrap_competition( BootstrapCompetitionParams $params ) {
		$competition = $this->create_competition( $params->name );
		$this->create_group_categories( $competition, $params->create_default_crew_groups );
	}

	private function create_competition( string $name ) {
		$competition       = new Competition();
		$competition->name = $name;

		$competition_id = $this->competition_dao->create( $competition );
		if ( $competition_id === false ) {
			throw new Exception( 'Could not create competition.' );
		}
		$competition = $this->competition_dao->get( $competition_id );
		return $competition;
	}

	private function create_group_categories( Competition $competition, bool $create_default_crew_groups ) {
		$rule_sets = array(
			'Crew Members'       => new CrewMembersRuleSet(),
			'Young Participants' => new YoungParticipantsRuleSet(),
			'Older Participants' => new OlderParticipantsRuleSet()
		);
		foreach ( $rule_sets as $name => $rule_set ) {
			$props                 = new GroupCategory();
			$props->competition_id = $competition->id;
			$props->name           = $name;
			$props->set_rules( GroupCategoryRules::from_rule_set( $rule_set, $competition ) );
			$category_id = $this->category_dao->create( $props );
			if ( $category_id !== false && $create_default_crew_groups && $rule_set->is_crew() ) {
				$group_props                     = new Group();
				$group_props->competition_id     = $competition->id;
				$group_props->map_id             = null;
				$group_props->name               = $name;
				$group_props->note               = null;
				$group_props->city               = null;
				$group_props->is_always_editable = false;
				$group_props->category_id        = $category_id;
				$group_props->set_status( Group::DEFAULT_STATUS );

				$group_id = $this->group_dao->create( $group_props );
			}
		}
	}
}
