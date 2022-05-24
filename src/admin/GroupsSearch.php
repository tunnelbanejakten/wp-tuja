<?php

namespace tuja\admin;

use Exception;
use tuja\data\store\CompetitionDao;

class GroupsSearch {

	private $competition;

	public function __construct() {
		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $_GET['tuja_competition'] );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}

	public function get_scripts(): array {
		return [
			'admin-search.js'
		];
	}

	public function output() {
		$competition = $this->competition;

		$search_query_endpoint = add_query_arg(
			array(
				'action'           => 'tuja_search',
				'query'            => 'QUERY',
				'tuja_competition' => $this->competition->id,
			),
			admin_url( 'admin.php' )
		);
		$group_page_url_pattern = add_query_arg( array(
			'tuja_view'  => 'GroupMembers',
			'tuja_group' => 'GROUPID',
			'tuja_competition' => $this->competition->id,
		) );
	
		include( 'views/groups-search.php' );
	}

	public function print_menu() {
		$groups_start_page_link = add_query_arg(
			array(
				'tuja_competition' => $this->competition->id,
				'tuja_view'        => 'Groups',
			)
		);

		print BreadcrumbsMenu::create(
		)->add(
			BreadcrumbsMenu::item( 'Grupper', $groups_start_page_link )
		)->add(
			BreadcrumbsMenu::item( 'Sök' ),
		)->render();
	}
}