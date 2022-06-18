<?php
namespace tuja\admin;

use Exception;
use tuja\data\store\CompetitionDao;
use tuja\admin\BreadcrumbsMenu;

class Competition {
	protected $competition;
	protected $competition_dao;

	public function __construct() {
		$this->competition_dao = new CompetitionDao();

		if ( isset( $_GET['tuja_competition'] ) ) {
			$this->competition = $this->competition_dao->get( $_GET['tuja_competition'] );
		}

		$this->assert_set( 'Could not find competition', $this->competition );
	}

	protected function assert_set( $message, $obj ) {
		if ( ! isset( $obj ) || false === $obj ) {
			throw new Exception( $message );
		}
	}

	protected function assert_same( $message, $id1, $id2 ) {
		if ( $id1 !== $id2 ) {
			throw new Exception( $message );
		}
	}

	protected function add_static_menu( BreadcrumbsMenu $menu, array $items ) {
		$parents                       = array_values( class_parents( $this ) );
		$group_page_current            = 'Some title';
		$group_page_links              = array();
		$this_name                     = get_class( $this );
		$is_active_page_in_static_menu = in_array( $this_name, array_keys( $items ), true );
		foreach ( $items as $full_view_name => $title ) {
			$short_view_name = substr( $full_view_name, strrpos( $full_view_name, '\\' ) + 1 );
			$active          = $this_name === $full_view_name || ( ! $is_active_page_in_static_menu && in_array( $full_view_name, $parents, true ) );
			if ( $active ) {
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

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = BreadcrumbsMenu::create();

		return $this->add_static_menu(
			$menu,
			array(
				Competition::class         => 'Översikt',
				Groups::class              => 'Grupper',
				Scoreboard::class          => 'Poängställning',
				Review::class              => 'Svar att rätta',
				Messages::class            => 'Meddelanden',
				Forms::class               => 'Formulär',
				Stations::class            => 'Stationer',
				Maps::class                => 'Kartor',
				Reports::class             => 'Rapporter',
				Shortcodes::class          => 'Länkar',
				CompetitionSettings::class => 'Inställningar',
				CompetitionDelete::class   => 'Rensa',
			)
		);
	}

	public function print_menu() {
		print $this->create_menu( $_GET['tuja_view'], array_values( class_parents( $this ) ) )->render();
	}

	public function print_leaves_menu() {
		print $this->create_menu( $_GET['tuja_view'], array_values( class_parents( $this ) ) )->render_leaves();
	}

	public function output() {
		$competition = $this->competition;

		include( 'views/competition.php' );
	}
}
