<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Competition;
use tuja\data\store\CompetitionDao;

class CompetitionSettingsApp extends AbstractCompetitionSettings {
	const FIELD_SEPARATOR = '__';

	public function handle_post() {
		if ( ! isset( $_POST['tuja_competition_settings_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_competition_settings_action'] === 'save' ) {
			$this->competition_settings_save( $this->competition );
		}
	}

	public function competition_settings_save( Competition $competition ) {
		try {
			$competition->app_config = json_decode( stripslashes( $_POST['tuja_competition_settings_appconfig'] ) );

			$dao = new CompetitionDao();
			$dao->update( $competition );
		} catch ( Exception $e ) {
			// TODO: Reuse this exception handling elsewhere?
			AdminUtils::printException( $e );
		}
	}

	public function get_scripts(): array {
		return array(
			'admin-formgenerator.js',
			'jsoneditor.min.js',
			'admin-competition-app.js',
		);
	}

	public function output() {
		$this->handle_post();

		$competition = $this->competition_dao->get( $_GET['tuja_competition'] );

		$back_url = add_query_arg(
			array(
				'tuja_competition' => $competition->id,
				'tuja_view'        => 'CompetitionSettings',
			)
		);

		include( 'views/competition-settings-app.php' );
	}


	public function print_app_config_form( Competition $competition ) {
		$config_file       = __DIR__ . '/../data/store/CompetitionAppConfig.schema.json';
		$jsoneditor_config = file_get_contents( $config_file );
		$jsoneditor_values = json_encode( $competition->app_config );

		$field_name = 'tuja_competition_settings_appconfig';

		return sprintf(
			'
			<div class="tuja-appconfig-form" id="tuja-tab-appconfig">
				<input type="hidden" name="%s" id="%s" value="%s">
				<div class="tuja-admin-formgenerator-form" 
					data-schema="%s" 
					data-values="%s" 
					data-field-id="%s"
					data-root-name="%s"></div>
			</div>',
			$field_name,
			$field_name,
			htmlentities( $jsoneditor_values ),
			htmlentities( $jsoneditor_config ),
			htmlentities( $jsoneditor_values ),
			htmlentities( $field_name ),
			'tuja-admin-formgenerator-form-appconfig'
		);
	}


}
