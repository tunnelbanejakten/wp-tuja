<?php

namespace tuja\admin;

use tuja\data\store\GroupDao;

class Groups extends Competition {
	protected $group_dao;

	public function __construct() {
		parent::__construct();

		$this->group_dao = new GroupDao();
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		return $this->add_static_menu(
			$menu,
			array(
				GroupsList::class   => array( 'Laglistor', null ),
				ExtraPoints::class  => array( 'Bonusupoäng', null ),
				GroupsSearch::class => array( 'Sök', null ),
				GroupsStats::class  => array( 'Statistik', null ),
			)
		);
	}

	public function output() {
		include( 'views/groups.php' );
	}
}