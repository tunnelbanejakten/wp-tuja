<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Competition;
use tuja\data\model\ValidationException;
use tuja\data\store\CompetitionDao;
use tuja\util\DateUtils;
use tuja\util\Strings;

class CompetitionSettings {
	const FIELD_SEPARATOR = '__';

	public function handle_post() {
		if ( ! isset( $_POST['tuja_competition_settings_action'] ) ) {
			return;
		}

		$competition_dao = new CompetitionDao();
		$competition     = $competition_dao->get( $_GET['tuja_competition'] );

		if ( ! $competition ) {
			throw new Exception( 'Could not find competition' );
		}

		if ( $_POST['tuja_competition_settings_action'] === 'save' ) {
			$this->competition_settings_save_other( $competition );
		}
	}

	public function output() {
		$this->handle_post();

		$competition_dao      = new CompetitionDao();
		$competition          = $competition_dao->get( $_GET['tuja_competition'] );

		include( 'views/competition-settings.php' );
	}


	public function list_item_field_name( $list_name, $id, $field ) {
		return join( self::FIELD_SEPARATOR, array( $list_name, $field, $id ) );
	}

	public function competition_settings_save_other( Competition $competition ) {
		try {
			$competition->event_start          = DateUtils::from_date_local_value( $_POST['tuja_event_start'] );
			$competition->event_end            = DateUtils::from_date_local_value( $_POST['tuja_event_end'] );

			$dao = new CompetitionDao();
			$dao->update( $competition );
		} catch ( Exception $e ) {
			// TODO: Reuse this exception handling elsewhere?
			AdminUtils::printException( $e );
		}
	}
}
