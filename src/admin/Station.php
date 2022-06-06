<?php

namespace tuja\admin;

use tuja\data\store\CompetitionDao;
use tuja\data\store\StationDao;

class Station extends Stations {

	const ACTION_DELETE = 'delete';
	const ACTION_SAVE   = 'save';

	public function __construct() {
		parent::__construct();

		if ( isset( $_GET['tuja_station'] ) ) {
			$this->station = $this->station_dao->get( $_GET['tuja_station'] );
		}

		$this->assert_set( 'Station not found', $this->station );
		$this->assert_same( 'Station needs to belong to competition', $this->station->competition_id, $this->competition->id );
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		$stations_current = null;
		$stations_links   = array();
		$stations         = $this->station_dao->get_all_in_competition( $this->competition->id );
		foreach ( $stations as $station ) {
			$active = $station->id === $this->station->id;
			if ( $active ) {
				$stations_current = $station->name;
			}
			$link             = add_query_arg(
				array(
					'tuja_view'    => 'Station',
					'tuja_station' => $station->id,
				)
			);
			$stations_links[] = BreadcrumbsMenu::item( $station->name, $link, $active );
		}
		$menu->add(
			BreadcrumbsMenu::item( $stations_current ),
			...$stations_links,
		);

		return $menu;
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_station_action'] ) ) {
			return true;
		}

		$action = @$_POST['tuja_station_action'];
		if ( self::ACTION_SAVE === $action ) {
			$this->station->name = @$_POST['tuja_station_name'];

			$success = $this->station_dao->update( $this->station );

			if ( $success ) {
				$this->station = $this->station_dao->get( $_GET['tuja_station'] );
				AdminUtils::printSuccess( 'Ändringar sparade.' );
			} else {
				AdminUtils::printError( 'Kunde inte spara.' );
			}
		} elseif ( self::ACTION_DELETE === $action ) {
			$success = ( $this->station_dao->delete( $_GET['tuja_station'] ) === 1 );

			if ( $success ) {
				$back_url = add_query_arg(
					array(
						'tuja_competition' => $this->competition->id,
						'tuja_view'        => 'Stations',
					)
				);
				AdminUtils::printSuccess( sprintf( 'Stationen har tagits bort. Vad sägs om att gå till <a href="%s">stationslistan</a>?', $back_url ) );

				return false;
			} else {
				AdminUtils::printError( 'Kunde inte ta bort stationen.' );
			}
		}
		return true;
	}

	public function output() {
		$is_station_available = $this->handle_post();

		$competition = $this->competition;
		$station     = $this->station;
		$back_url    = add_query_arg(
			array(
				'tuja_competition' => $competition->id,
				'tuja_view'        => 'Stations',
			)
		);

		if ( $is_station_available ) {
			include 'views/station.php';
		}
	}
}
