<?php

namespace tuja\admin;

use tuja\data\model\Competition;
use tuja\data\store\CompetitionDao;

class Competitions {

	private $db_competition;

	public function __construct() {
		$this->db_competition = new CompetitionDao();
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] === 'competition_create' ) {
			$props       = new Competition();
			$props->name = $_POST['tuja_competition_name'];
			$this->db_competition->create( $props );
		}
	}

	public function get_scripts(): array {
		return [
		];
	}

	public function output() {
		$this->handle_post();

		$competitions = $this->db_competition->get_all();

		include( 'views/index.php' );
	}
}