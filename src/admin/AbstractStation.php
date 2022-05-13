<?php
namespace tuja\admin;

use tuja\data\store\StationDao;

use tuja\data\store\CompetitionDao;


class AbstractStation {

	protected $station;
	protected $competition;
	protected $station_dao;
	protected $competition_dao;

	public function __construct() {
		$this->station_dao     = new StationDao();
		$this->competition_dao = new CompetitionDao();

		if ( isset( $_GET['tuja_station'] ) ) {
			$this->station     = $this->station_dao->get( $_GET['tuja_station'] );
			$this->competition = $this->competition_dao->get( $this->station->competition_id );
		} elseif ( isset( $_GET['tuja_competition'] ) ) {
			$this->competition = $this->competition_dao->get( $_GET['tuja_competition'] );
		}

		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}

	protected function print_menu() {
		$menu = BreadcrumbsMenu::create();

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

		print $menu->render();
	}

}
