<?php

namespace tuja\admin;

use DateTime;
use Exception;
use tuja\admin\Groups;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\Map;
use tuja\data\model\Person;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MapDao;
use tuja\data\store\PersonDao;
use tuja\util\rules\RuleResult;

class GroupsStats extends Groups {
	protected $group_dao;

	public function __construct() {
		parent::__construct();

		$this->group_dao = new GroupDao();
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );
		return $menu->add(
			BreadcrumbsMenu::item( 'Statistik' ),
		);
	}

	public function handle_post() {

	}

	public function get_scripts(): array {
		return array();
	}

	public function output() {
		$this->handle_post();

		$db_groups = new GroupDao();
		$db_people = new PersonDao();

		$competition = $this->competition;

		$groups_per_category = array();
		$groups_competing    = 0;
		$people_competing    = 0;
		$people_following    = 0;
		$people_checkedin    = 0;

		$groups_data         = array();
		$groups              = $db_groups->get_all_in_competition( $competition->id, false );
		$competing_group_ids = array();

		foreach ( $groups as $group ) {
			$group_data             = array();
			$group_data['category'] = $group->get_category();

			if ( ! $group->is_crew ) {
				$groups_competing                                      += 1;
				@$groups_per_category[ $group_data['category']->name ] += 1;
				$competing_group_ids[]                                  = $group->id;
			}

			$groups_data[] = $group_data;
		}

		$people = $db_people->get_all_in_competition( $competition->id );
		foreach ( $people as $person ) {
			// Ignore crew members:
			if ( in_array( $person->group_id, $competing_group_ids ) ) {
				$people_competing += $person->is_competing();
				$people_following += $person->is_adult_supervisor();
				$people_checkedin += $person->get_status() === Person::STATUS_CHECKEDIN ? 1 : 0;
			}
		}

		include( 'views/groups-stats.php' );
	}
}
