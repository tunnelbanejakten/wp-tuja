<?php

namespace tuja\admin;

use tuja\data\model\Competition;
use tuja\data\store\CompetitionDao;

class Competitions {

	private $competition_dao;

	public function __construct() {
		$this->competition_dao = new CompetitionDao();
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] === 'competition_create' ) {
			$props       = new Competition();
			$props->name = $_POST['tuja_competition_name'];
			$this->competition_dao->create( $props );
		}
	}

	public function get_scripts(): array {
		return [
		];
	}

	public function output() {
		$this->handle_post();

		$competitions = $this->competition_dao->get_all();

		include( 'views/index.php' );
	}
}