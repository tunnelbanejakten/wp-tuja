<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Competition;
use tuja\data\store\CompetitionDao;
use tuja\util\StateMachine;

class CompetitionSettingsGroupLifecycle extends CompetitionSettings {
	const FIELD_SEPARATOR = '__';

	public function handle_post() {
		if ( ! isset( $_POST['tuja_competition_settings_action'] ) ) {
			return;
		}

		$competition     = $this->competition_dao->get( $_GET['tuja_competition'] );
		$this->assert_set( 'Could not find competition', $competition );

		if ( $_POST['tuja_competition_settings_action'] === 'save' ) {
			$this->competition_settings_save( $competition );
		}
	}

	public function competition_settings_save( Competition $competition ) {
		try {
			$competition->initial_group_status = $_POST['tuja_competition_settings_initial_group_status'] ?: null;

			$dao = new CompetitionDao();
			$dao->update( $competition );
		} catch ( Exception $e ) {
			// TODO: Reuse this exception handling elsewhere?
			AdminUtils::printException( $e );
		}
	}

	public function get_scripts(): array {
		return [
			'mermaid.min.js',
			'admin-competition-group-lifecycle.js',
		];
	}

	public function output() {
		$this->handle_post();

		$competition_dao      = new CompetitionDao();
		$competition          = $competition_dao->get( $_GET['tuja_competition'] );

		$group_status_transitions_definitions = StateMachine::as_mermaid_chart_definition( \tuja\data\model\Group::STATUS_TRANSITIONS );

		$back_url = add_query_arg( array(
			'tuja_competition' => $competition->id,
			'tuja_view'        => 'CompetitionSettings'
		) );

		include( 'views/competition-settings-group-lifecycle.php' );
	}
}