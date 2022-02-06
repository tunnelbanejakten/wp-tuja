<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\Station;
use tuja\data\store\StationDao;
use tuja\data\store\StationPointsDao;
use tuja\frontend\AbstractCrewMemberView;
use tuja\frontend\router\ReportPointsInitiator;
use tuja\util\concurrency\LockValuesList;
use tuja\view\FieldNumber;

class ReportPoints extends AbstractCrewMemberView {
	const ACTION_FIELD_NAME    = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'action';
	const STATION_FIELD_PREFIX = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'station';

	private $points_dao;
	private $station_dao;

	public function __construct( string $url, string $person_key, string $station_key ) {
		parent::__construct( $url, $person_key, 'Rapportera poäng' );
		$this->station_key = $station_key;
		$this->points_dao  = new StationPointsDao();
		$this->station_dao = new StationDao();
	}

	function output() {
		$form = $this->get_form_html();
		include( 'views/points-override.php' );
	}

	private function get_station() {
		if ( isset( $this->station_key ) ) {
			return $this->station_dao->get_by_key( $this->station_key );
		}
		return false;
	}

	public function get_form_html(): string {
		$html_sections = array();

		// Save points
		if ( isset( $_POST[ self::ACTION_FIELD_NAME ] ) && $_POST[ self::ACTION_FIELD_NAME ] == 'update' ) {
			$errors = $this->update_points();
			if ( empty( $errors ) ) {
				$html_sections[] = sprintf( '<p class="tuja-message tuja-message-success">%s</p>', 'Poängen har sparats.' ); // TODO: Extract to strings.ini
			} else {
				$html_sections[] = sprintf( '<p class="tuja-message tuja-message-error">%s</p>', join( '. ', $errors ) );
			}
		}

		$station = $this->get_station();

		// If a station and question station has been selected, display the questions with current points and a save button
		if ( $station ) {
			$groups          = $this->get_participant_groups();

			$current_points = $this->points_dao->get_by_competition( $this->competition_id );
			$current_points = array_combine(
				array_map(
					function ( $points ) {
						return $points->station_id . self::FIELD_NAME_PART_SEP . $points->group_id;
					},
					$current_points
				),
				array_values( $current_points )
			);

			array_walk(
				$groups,
				function ( Group $group ) use ( &$html_sections, $station, $current_points ) {
					$text            = $group->name;
					$html_sections[] = sprintf( '<p>%s</p>', $this->render_points_field( $text, $station->id, $group->id, $current_points ) );
				}
			);

			$html_sections[] = $this->html_optimistic_lock();

			$html_sections[] = sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="update">Spara</button></div>', self::ACTION_FIELD_NAME ); // TODO: Extract to strings.ini

			$html_sections[] = sprintf( '<a href="%s">Tillbaka</a>', ReportPointsInitiator::link_all( $this->person ) );
		} else {
			$stations = $this->station_dao->get_all_in_competition( $this->competition_id );
			array_walk(
				$stations,
				function ( Station $station ) use ( &$html_sections ) {
					$html_sections[] = sprintf(
						'<p><a href="%s">%s</a></p>',
						ReportPointsInitiator::link_one( $this->person, $station ),
						$station->name
					);
				}
			);
		}

		return join( $html_sections );
	}

	private function render_points_field( $text, $station_id, $group_id, $current_points ): string {
		$key        = self::key( $station_id, $group_id );
		$points     = isset( $current_points[ $key ] ) ? $current_points[ $key ]->points : null;
		$field      = new FieldNumber( $text );
		$field_name = self::STATION_FIELD_PREFIX . self::FIELD_NAME_PART_SEP . $key;

		return $field->render( $field_name, $points );
	}

	public function update_points(): array {
		$errors = array();

		$form_values = array_filter(
			$_POST,
			function ( $key ) {
				return substr( $key, 0, strlen( self::STATION_FIELD_PREFIX ) ) === self::STATION_FIELD_PREFIX;
			},
			ARRAY_FILTER_USE_KEY
		);

		try {
			$this->check_optimistic_lock();
		} catch ( Exception $e ) {
			// We do not want to present the previously inputted values in case we notice that another user has assigned score to the same questions.
			// The responses inputted for the previously selected group are not relevant anymore (they are, in fact, probably incorrect).
			foreach ( $form_values as $field_name => $field_value ) {
				unset( $_POST[ $field_name ] );
			}

			return array( $e->getMessage() );
		}

		foreach ( $form_values as $field_name => $field_value ) {
			try {
				list( , , $station_id, $group_id ) = explode( self::FIELD_NAME_PART_SEP, $field_name );

				$this->points_dao->set( $group_id, $station_id, is_numeric( $field_value ) ? intval( $field_value ) : null );
			} catch ( Exception $e ) {
				// TODO: Use the key to display the error message next to the problematic text field.
				$errors[ $field_name ] = $e->getMessage();
			}
		}

		return $errors;
	}

	private static function key( int $station_id, int $group_id ): string {
		return $station_id . self::FIELD_NAME_PART_SEP . $group_id;
	}

	function get_optimistic_lock(): LockValuesList {
		$lock = new LockValuesList();

		$groups  = $this->get_participant_groups();
		$station = $this->get_station();

		$keys = array_map(
			function ( Group $group ) use ( $station ) {
				return self::key( $station->id, $group->id );
			},
			$groups
		);

		$current_points = $this->points_dao->get_by_competition( $this->competition_id );
		$points_by_key  = array_combine(
			array_map(
				function ( $points ) {
					return self::key( $points->station_id, $points->group_id );
				},
				$current_points
			),
			array_values( $current_points )
		);

		array_walk(
			$keys,
			function ( string $key ) use ( $points_by_key, $lock ) {
				if ( isset( $points_by_key[ $key ] ) && null !== $points_by_key[ $key ]->created ) {
					$lock->add_value( $key, $points_by_key[ $key ]->created->getTimestamp() );
				} else {
					$lock->add_value( $key, 0 );
				}
			},
			0
		);

		return $lock;
	}
}
