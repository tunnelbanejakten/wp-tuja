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
		$group_page_current_link       = null;
		$group_page_links              = array();
		$this_name                     = get_class( $this );
		$all_keys                      = array_keys( $items );
		$is_active_page_in_static_menu = in_array( $this_name, $all_keys, true );
		foreach ( $items as $full_view_name => $config ) {
			list ($title, $header) = $config;
			$short_view_name       = substr( $full_view_name, strrpos( $full_view_name, '\\' ) + 1 );
			$active                = ( $is_active_page_in_static_menu && $this_name === $full_view_name ) || ( ! $is_active_page_in_static_menu && in_array( $full_view_name, $parents, true ) );
			$link                  = add_query_arg(
				array(
					'tuja_competition' => $this->competition->id,
					'tuja_view'        => $short_view_name,
				)
			);
			if ( $active ) {
				$group_page_current      = $title;
				$group_page_current_link = $link;
			}
			$group_page_links[] = BreadcrumbsMenu::item( $title, $link, $active, $header );
		}

		return $menu->add(
			BreadcrumbsMenu::item( $group_page_current, $group_page_current_link ),
			...$group_page_links,
		);
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = BreadcrumbsMenu::create();

		return $this->add_static_menu(
			$menu,
			array(
				Competition::class         => array( 'Översikt', '' ),

				Scoreboard::class          => array( 'Poängställning', 'Under tävlingen' ),
				Review::class              => array( 'Svar att rätta', 'Under tävlingen' ),

				Forms::class               => array( 'Formulär', 'Uppgifter' ),
				Stations::class            => array( 'Stationer', 'Uppgifter' ),
				Maps::class                => array( 'Kartor', 'Uppgifter' ),
				Duels::class               => array( 'Dueller', 'Uppgifter' ),

				Groups::class              => array( 'Grupper', 'Administration' ),
				Messages::class            => array( 'Meddelanden', 'Administration' ),
				Reports::class             => array( 'Rapporter', 'Administration' ),
				Payments::class            => array( 'Betalning', 'Administration' ),
				Uploads::class             => array( 'Bilder', 'Administration' ),
				CompetitionSettings::class => array( 'Inställningar', 'Administration' ),
				CompetitionDelete::class   => array( 'Rensa', 'Administration' ),
			)
		);
	}

	public function print_menu() {
		print $this->create_menu( $_GET['tuja_view'], array_values( class_parents( $this ) ) )->render();
	}

	public function print_root_menu() {
		print $this->create_menu( $_GET['tuja_view'], array_values( class_parents( $this ) ) )->render_root_menu();
	}

	public function print_leaves_menu( bool $large = false ) {
		print $this->create_menu( $_GET['tuja_view'], array_values( class_parents( $this ) ) )->render_leaves( $large );
	}

	public function output() {
		$competition = $this->competition;

		include( 'views/competition.php' );
	}
}
