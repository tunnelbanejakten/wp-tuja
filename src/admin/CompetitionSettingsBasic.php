<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Competition;
use tuja\data\model\ValidationException;
use tuja\util\DateUtils;


class CompetitionSettingsBasic extends AbstractCompetitionSettings {
	private function handle_post() {
		if ( ! isset( $_POST['tuja_competition_settings_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_competition_settings_action'] === 'save' ) {
			$this->save_changes( $this->competition );
		}
	}

	public function output() {
		$this->handle_post();

		$competition     = $this->competition_dao->get( $_GET['tuja_competition'] );

		include( 'views/competition-settings-basic.php' );
	}

	private function save_changes( Competition $competition ) {
		try {
			$competition->name        = $_POST['tuja_event_name'];
			$competition->event_start = DateUtils::from_date_local_value( $_POST['tuja_event_start'] );
			$competition->event_end   = DateUtils::from_date_local_value( $_POST['tuja_event_end'] );

			$this->competition_dao->update( $competition );
		} catch ( Exception $e ) {
			// TODO: Reuse this exception handling elsewhere?
			AdminUtils::printException( $e );
		}
	}
}
