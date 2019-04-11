<?php

namespace tuja\admin;

use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\util\score\ScoreCalculator;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\CompetitionDao;
use tuja\data\model\ValidationException;

class Shortcodes {

	private $competition;

	public function __construct() {
		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $_GET['tuja_competition'] );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}


	public function handle_post() {

	}


	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		$forms = ( new FormDao() )->get_all_in_competition( $competition->id );

		include( 'views/shortcodes.php' );
	}
}