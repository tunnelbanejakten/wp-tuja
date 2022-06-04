<?php
namespace tuja\admin;

use Exception;
use tuja\data\store\CompetitionDao;
use tuja\admin\BreadcrumbsMenu;

abstract class AbstractCompetitionPage {
	protected $competition;
	protected $competition_dao;

	public function __construct() {
		$this->competition_dao = new CompetitionDao();

		if ( isset( $_GET['tuja_competition'] ) ) {
			$this->competition = $this->competition_dao->get( $_GET['tuja_competition'] );
		}

		if ( ! $this->competition ) {
			throw new Exception( 'Could not find competition' );

			return;
		}
	}

	protected function create_menu( string $current_view_name ): BreadcrumbsMenu {
		$menu = BreadcrumbsMenu::create();

		//
		// Zeroth level
		//

		$group_page_current = 'Some title';
		$group_page_links   = array();
		$items              = array(
			Groups::class              => 'Grupper',
			Scoreboard::class          => 'Poängställning',
			Review::class              => 'Svar att rätta',
			Messages::class            => 'Meddelanden',
			Competition::class         => 'Formulär',
			Stations::class            => 'Stationer',
			Maps::class                => 'Kartor',
			Reports::class             => 'Rapporter',
			Shortcodes::class          => 'Länkar',
			CompetitionSettings::class => 'Inställningar',
			CompetitionDelete::class   => 'Rensa',
		);
		foreach ( $items as $full_view_name => $title ) {
			$short_view_name = substr( $full_view_name, strrpos( $full_view_name, '\\' ) + 1 );
			$active          = $short_view_name === $current_view_name;
			if ( $active || ( $current_view_name === 'GroupMember' && $short_view_name === 'GroupMembers' ) ) {
				$group_page_current = $title;
			}
			$link               = add_query_arg(
				array(
					'tuja_competition' => $this->competition->id,
					'tuja_view'        => $short_view_name,
				)
			);
			$group_page_links[] = BreadcrumbsMenu::item( $title, $link, $active );
		}

		return $menu->add(
			BreadcrumbsMenu::item( $group_page_current ),
			...$group_page_links,
		);
	}

	public function print_menu() {
		print $this->create_menu( $_GET['tuja_view'] )->render();
	}

	public function print_leaves_menu() {
		print $this->create_menu( $_GET['tuja_view'] )->render_leaves();
	}
}
