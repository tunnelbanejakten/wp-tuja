<?php

namespace tuja\admin;

class GroupsSearch extends Groups {
	public function get_scripts(): array {
		return array(
			'admin-search.js',
		);
	}

	public function output() {
		$competition = $this->competition;

		$search_query_endpoint  = add_query_arg(
			array(
				'action'           => 'tuja_search',
				'query'            => 'QUERY',
				'tuja_competition' => $this->competition->id,
			),
			admin_url( 'admin.php' )
		);
		$group_page_url_pattern = add_query_arg(
			array(
				'tuja_view'        => 'GroupMembers',
				'tuja_group'       => 'GROUPID',
				'tuja_competition' => $this->competition->id,
			)
		);

		include( 'views/groups-search.php' );
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );
		$groups_start_page_link = add_query_arg(
			array(
				'tuja_competition' => $this->competition->id,
				'tuja_view'        => 'Groups',
			)
		);

		return $menu->add(
			BreadcrumbsMenu::item( 'Sök' ),
		);
	}
}
