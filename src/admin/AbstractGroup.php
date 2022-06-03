<?php

namespace tuja\admin;

use Exception;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupDao;

class AbstractGroup {
	protected $group;
	protected $competition;
	protected $group_dao;

	public function __construct() {
		$this->group_dao = new GroupDao();
		$this->group     = $this->group_dao->get( $_GET['tuja_group'] );
		if ( ! $this->group ) {
			print 'Could not find group';

			return;
		}

		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $this->group->competition_id );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}

	protected function create_menu( $current_view_name ) {
		//
		// First level
		//

		$groups_start_page_link = add_query_arg(
			array(
				'tuja_competition' => $this->competition->id,
				'tuja_view'        => 'Groups',
			)
		);

		//
		// Second level
		//

		$groups_current = null;
		$groups_links   = array();
		$dao            = new GroupDao();
		$groups         = $dao->get_all_in_competition( $this->competition->id );
		foreach ( $groups as $group ) {
			if ( $group->id === $this->group->id ) {
				$groups_current = $group->name;
			}
			$link = add_query_arg(
				array(
					'tuja_competition' => $this->competition->id,
					'tuja_view'        => 'Group',
					'tuja_group'       => $group->id,
				)
			);
			$groups_links[] = BreadcrumbsMenu::item( $group->name, $link );
		}

		//
		// Third level
		//

		$group_page_current = null;
		$group_page_links   = array();
		$items              = array(
			Group::class        => 'Allmänt',
			GroupMembers::class => 'Deltagare',
			GroupLinks::class   => 'Länkar',
			GroupScore::class   => 'Svar och poäng',
			GroupEvents::class  => 'Tidsbegränsade frågor som visats',
		);
		foreach ( $items as $full_view_name => $title ) {
			$short_view_name = substr( $full_view_name, strrpos( $full_view_name, '\\' ) + 1 );
			if ( $short_view_name === $current_view_name || ( $current_view_name === 'GroupMember' && $short_view_name === 'GroupMembers' ) ) {
				$group_page_current = $title;
			}
			$link               = add_query_arg(
				array(
					'tuja_competition' => $this->competition->id,
					'tuja_view'        => $short_view_name,
				)
			);
			$group_page_links[] = BreadcrumbsMenu::item( $title, $link );
		}

		$menu = BreadcrumbsMenu::create(
		)->add(
			BreadcrumbsMenu::item( 'Grupper', $groups_start_page_link )
		)->add(
			BreadcrumbsMenu::item( $groups_current ),
			...$groups_links,
		)->add(
			BreadcrumbsMenu::item( $group_page_current ),
			...$group_page_links,
		);

		return $menu;
	}

	public function print_menu() {
		print $this->create_menu( $_GET['tuja_view'] )->render();
	}
}
