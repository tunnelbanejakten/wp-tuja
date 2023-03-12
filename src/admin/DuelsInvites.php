<?php

namespace tuja\admin;

use tuja\controller\DuelsController;
use tuja\data\store\DuelDao;

class DuelsInvites extends Duels {
	protected $duel_dao;

	public function __construct() {
		parent::__construct();
		$this->duel_dao = new DuelDao();
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] == 'create_duels' ) {
			$min_duel_participant_count = intval( $_POST['tuja_min_duel_participant_count'] );

			( new DuelsController( $this->competition ) )->generate_invites( $min_duel_participant_count );
		}
	}


	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		$duel_groups = $this->duel_dao->get_duels_by_competition( $competition->id );

		include 'views/duels-invites.php';
	}
}
