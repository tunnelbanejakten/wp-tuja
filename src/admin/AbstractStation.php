<?php
namespace tuja\admin;

use tuja\data\store\StationDao;

use tuja\data\store\CompetitionDao;


class AbstractStation extends AbstractCompetitionPage {

	protected $station;
	protected $station_dao;

	public function __construct() {
		parent::__construct();
		$this->station_dao     = new StationDao();
		$this->competition_dao = new CompetitionDao();

		if ( isset( $_GET['tuja_station'] ) ) {
			$this->station = $this->station_dao->get( $_GET['tuja_station'] );
			// TODO: Validate that station belongs to competition
		}
	}

	protected function create_menu( string $current_view_name ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name );

		//
		// First level
		//
		$groups_start_page_link = $_GET['tuja_view'] !== 'Stations' ? add_query_arg(
			array(
				'tuja_view'    => 'Stations',
				'tuja_station' => null,
			)
		) : null;
		$menu->add(
			BreadcrumbsMenu::item( 'Stationer', $groups_start_page_link )
		);

		//
		// Second level
		//

		if ( isset( $this->station ) ) {
			$stations_current = null;
			$stations_links   = array();
			$stations         = $this->station_dao->get_all_in_competition( $this->competition->id );
			foreach ( $stations as $station ) {
				if ( $station->id === $this->station->id ) {
					$stations_current = $station->name;
				} else {
					$link             = add_query_arg(
						array(
							'tuja_view'    => 'Station',
							'tuja_station' => $station->id,
						)
					);
					$stations_links[] = BreadcrumbsMenu::item( $station->name, $link );
				}
			}
			$menu->add(
				BreadcrumbsMenu::item( $stations_current ),
				...$stations_links,
			);
		}

		return $menu;
	}

}
