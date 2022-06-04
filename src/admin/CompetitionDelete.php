<?php

namespace tuja\admin;

use Exception;
use tuja\controller\DeleteCompetitionController;
use tuja\controller\AnonymizeController;

class CompetitionDelete extends AbstractCompetitionPage {
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
		} elseif ( @$_POST['tuja_action'] == 'anonymize' ) {
			if ( @$_POST['tuja_anonymizer_confirm'] !== 'true' ) {
				AdminUtils::printError( 'Du måste kryssa för att du verkligen vill anonymisera personuppgifterna först.' );

				return true;
			}

			try {
				$controller = new AnonymizeController( $this->competition );
				$filter     = @$_POST['tuja_anonymizer_filter'];
				if ( $filter === 'participants' ) {
					$controller->anonymize_participants_incl_contacts();
				} elseif ( $filter === 'non_contacts' ) {
					$controller->anonymize_participants_excl_contacts();
				} elseif ( $filter === 'all' ) {
					$controller->anonymize_all();
				} else {
					AdminUtils::printError( 'Du måste välja vilket urval av personuppgifter du vill anonymisera.' );

					return true;
				}
				AdminUtils::printSuccess( 'Klart. Personuppgifterna har anonymiserats.' );
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
