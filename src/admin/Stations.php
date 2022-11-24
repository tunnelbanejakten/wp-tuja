<?php

namespace tuja\admin;

use tuja\data\model\Station;
use tuja\data\store\StationDao;
use tuja\data\model\ValidationException;

class Stations extends Competition {

	protected $station_dao;

	public function __construct() {
		parent::__construct();
		$this->station_dao = new StationDao();
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] == 'station_create' ) {
			$props                 = new Station();
			$props->name           = $_POST['tuja_station_name'];
			$props->competition_id = $this->competition->id;
			try {
				$this->station_dao->create( $props );
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			}
		}
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		return $this->add_static_menu(
			$menu,
			array(
				Stations::class              => array( 'Översikt', null ),
				StationsManageTickets::class => array( 'Hantera biljetter', null ),
				StationsPoints::class        => array( 'Dela ut poäng', null ),
				StationsTicketing::class     => array( 'Konfigurera biljettsystem', null ),
			)
		);
	}

	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		$stations = $this->station_dao->get_all_in_competition( $competition->id );

		$ticketing_url      = add_query_arg(
			array(
				'tuja_view' => 'StationsTicketing',
			)
		);
		$points_url         = add_query_arg(
			array(
				'tuja_view' => 'StationsPoints',
			)
		);
		$manage_tickets_url = add_query_arg(
			array(
				'tuja_view' => 'StationsManageTickets',
			)
		);
		include( 'views/stations.php' );
	}
}
