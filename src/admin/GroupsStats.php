<?php

namespace tuja\admin;

use DateTime;
use Exception;
use tuja\admin\Groups;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\Map;
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

		$competition = $this->competition;

		$groups_per_category = array();
		$groups_competing    = 0;
		$people_competing    = 0;
		$people_following    = 0;

		$groups_data = array();
		$groups      = $db_groups->get_all_in_competition( $competition->id, true );

		foreach ( $groups as $group ) {
			$group_data = array();
			$group_data['category'] = $group->get_category();

			if ( ! $group_data['category']->get_rules()->is_crew() && $group->get_status() !== Group::STATUS_DELETED ) {
				$groups_competing                                      += 1;
				$people_competing                                      += $group->count_competing;
				$people_following                                      += $group->count_follower;
				@$groups_per_category[ $group_data['category']->name ] += 1;
			}

			$groups_data[] = $group_data;
		}

		include( 'views/groups-stats.php' );
	}
}
