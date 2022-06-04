<?php

namespace tuja\admin;

use tuja\data\store\FormDao;

class Shortcodes extends AbstractCompetitionPage {

	public function handle_post() {

	}

	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		$forms = ( new FormDao() )->get_all_in_competition( $competition->id );

		include( 'views/shortcodes.php' );
	}
}