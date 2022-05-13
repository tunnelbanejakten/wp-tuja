<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Competition;
use tuja\data\store\CompetitionDao;
use tuja\data\store\StringsDao;
use tuja\util\Strings;

class CompetitionSettingsStrings extends AbstractCompetitionSettings {
	const FIELD_SEPARATOR = '__';

	public function handle_post() {
		if ( ! isset( $_POST['tuja_competition_settings_action'] ) ) {
			return;
		}

		$competition = $this->competition_dao->get( $_GET['tuja_competition'] );

		if ( $_POST['tuja_competition_settings_action'] === 'save' ) {
			$this->competition_settings_save_strings( $competition );
		}
	}

	public function get_scripts(): array {
		return array(
			'admin-templateeditor.js',
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

		include( 'views/competition-settings-strings.php' );
	}


	public function list_item_field_name( $list_name, $id, $field ) {
		return join( self::FIELD_SEPARATOR, array( $list_name, $field, $id ) );
	}

	private function competition_settings_save_strings( Competition $competition ) {
		$final_list = Strings::get_list();

		$updated_list = array();
		foreach ( array_keys( $final_list ) as $key ) {
			$submitted_value = str_replace(
				"\r\n",
				"\n",
				@$_POST[ self::string_field_name( $key ) ] ?: ''
			);
			if ( ! Strings::is_default_value( $key, $submitted_value ) ) {
				$updated_list[ $key ] = $submitted_value;
			}
		}

		( new StringsDao() )->set_all( $competition->id, $updated_list );

		Strings::init( $competition->id, true );
	}

	public static function string_field_name( string $key ) {
		return 'tuja_strings__' . str_replace( '.', '_', $key );
	}
}
