<?php

namespace tuja\admin;

use Exception;
use tuja\controller\DeleteCompetitionController;
use tuja\data\store\CompetitionDao;

class CompetitionDelete {

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
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return true;
		}

		if ( @$_POST['tuja_action'] == 'competition_delete' ) {
			if ( @$_POST['tuja_competition_delete_confirm'] !== 'true' ) {
				AdminUtils::printError( 'Du måste kryssa för att du verkligen vill ta bort tävlingen först.' );

				return true;
			}
			try {
				$controller = new DeleteCompetitionController();
				$controller->delete( $this->competition );

				$url = add_query_arg(
					array(
						'tuja_view' => 'Competitions',
					)
				);

				AdminUtils::printSuccess( sprintf( 'Tävlingen har tagits bort. Vad sägs om att gå till <a href="%s">startsidan</a> för att kanske skapa en ny?', $url ) );

				return false;
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
			}
		}
		return true;
	}


	public function output() {
		$is_competition_available = $this->handle_post();

		$competition = $this->competition;

		if ( $is_competition_available ) {
			include 'views/competition-delete.php';
		}
	}
}
