<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Points;
use tuja\data\model\Station;
use tuja\data\store\EventDao;
use tuja\data\store\ExtraPointsDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MessageDao;
use tuja\data\store\QuestionPointsOverrideDao;
use tuja\data\store\StationPointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\data\store\StationDao;
use tuja\util\score\ScoreCalculator;

class GroupScore extends Group {

	const DEFAULT_QUESTION_FILTER    = ResponseDao::QUESTION_FILTER_ALL;
	const QUESTION_FILTER_URL_PARAM  = 'tuja_group_question_filter';

	private $review_component;
	private $extra_points_dao;
	private $station_dao;
	private $station_points_dao;

	public function __construct() {
		parent::__construct();
		$this->review_component   = new ReviewComponent( $this->competition );
		$this->extra_points_dao   = new ExtraPointsDao();
		$this->station_dao        = new StationDao();
		$this->station_points_dao = new StationPointsDao();
	}

	public function get_scripts(): array {
		return array(
			'admin-review-component.js',
		);
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_points_action'] ) ) {
			return;
		}

		@list( $action, $parameter ) = explode( '__', @$_POST['tuja_points_action'] );

		if ( $action === 'save' ) {

			$result = $this->review_component->handle_post(
				$_GET[ self::QUESTION_FILTER_URL_PARAM ] ?: self::DEFAULT_QUESTION_FILTER,
				array( $this->group )
			);

			if ( $result['skipped'] > 0 ) {
				AdminUtils::printError(
					sprintf(
						'Kunde inte uppdatera poängen för %d frågor. Någon annan hann före.',
						$result['skipped']
					)
				);
			}
			if ( count( $result['marked_as_reviewed'] ) > 0 ) {
				AdminUtils::printSuccess(
					sprintf(
						'Svar på %d frågor har markerats som kontrollerade.',
						count( $result['marked_as_reviewed'] )
					)
				);
			}
		} elseif ( $action === 'save_stations_and_extras' ) {
			$this->handle_save_extra_points();
			$this->handle_save_station_points();
		}
	}

	private function handle_save_extra_points() {
		$all_names = $this->all_extra_points_names();

		array_walk(
			$all_names,
			function ( string $name ) {
				$name_field_key   = self::get_extra_points_label_field_key( $name );
				$points_field_key = self::get_extra_points_field_key( $name );

				$updated_name = ! empty( $_POST[ $name_field_key ] ) ? $_POST[ $name_field_key ] : $name;
				if ( isset( $_POST[ $points_field_key ] ) && is_numeric( $_POST[ $points_field_key ] ) ) {
					$points = intval( $_POST[ $points_field_key ] );
					$this->extra_points_dao->set( $this->group->id, $updated_name, $points );
				} else {
					$this->extra_points_dao->set( $this->group->id, $updated_name, null );
				}
			}
		);
	}

	private static function get_extra_points_field_key( $name ) {
		return join( '__', array( 'tuja', 'extra-points', crc32( $name ) ) );
	}

	private static function get_extra_points_label_field_key( $name ) {
		return join( '__', array( 'tuja', 'extra-points-label', crc32( $name ) ) );
	}

	private function all_extra_points_names(): array {
		$existing_names = $this->extra_points_dao->all_names( $this->competition->id );
		return array_merge( $existing_names, array( '' ) );
	}

	private function handle_save_station_points() {
		$stations = $this->station_dao->get_all_in_competition( $this->competition->id );

		array_walk(
			$stations,
			function ( Station $station ) {
				$key = self::get_station_points_field_key( $station->id );
				if ( isset( $_POST[ $key ] ) && is_numeric( $_POST[ $key ] ) ) {
					$points = intval( $_POST[ $key ] );
					$this->station_points_dao->set( $this->group->id, $station->id, $points );
				} else {
					$this->station_points_dao->set( $this->group->id, $station->id, null );
				}
			}
		);
	}

	private static function get_station_points_field_key( $station_id ) {
		return join( '__', array( 'tuja', 'station-points', $station_id ) );
	}

	public function output() {
		$this->handle_post();

		$group       = $this->group;
		$competition = $this->competition;

		$db_question       = new QuestionDao();
		$db_question_group = new QuestionGroupDao();
		$db_response       = new ResponseDao();
		$db_groups         = new GroupDao();
		$db_points         = new QuestionPointsOverrideDao();
		$db_station_points = $this->station_points_dao;
		$db_extra_points   = $this->extra_points_dao;
		$db_stations       = $this->station_dao;
		$db_message        = new MessageDao();
		$db_event          = new EventDao();

		$score_calculator = new ScoreCalculator(
			$competition->id,
			$db_question,
			$db_question_group,
			$db_response,
			$db_groups,
			$db_points,
			$db_station_points,
			$db_extra_points,
			$db_event
		);
		$score_result     = $score_calculator->score( $group );

		$responses = $db_response->get_latest_by_group( $group->id );

		$extra_points_by_key = array();
		$extra_points        = $this->extra_points_dao->get_by_group( $this->group->id );
		array_walk(
			$extra_points,
			function ( Points $points ) use ( &$extra_points_by_key ) {
				$key                         = self::get_extra_points_field_key( $points->name );
				$extra_points_by_key[ $key ] = $points->points;
			}
		);
		$all_extra_points_names = $this->all_extra_points_names();

		// TODO: Remove $points_overrides?
		$points_overrides              = $db_points->get_by_group( $group->id );
		$points_overrides_per_question = array_combine(
			array_map(
				function ( $points ) {
					return $points->form_question_id;
				},
				$points_overrides
			),
			array_values( $points_overrides )
		);

		$review_component = $this->review_component;

		$stations = $db_stations->get_all_in_competition( $competition->id );

		$station_points_by_key = array();
		$station_points        = $this->station_points_dao->get_by_group( $this->group->id );
		array_walk(
			$station_points,
			function ( Points $points ) use ( &$station_points_by_key ) {
				$key                           = self::get_station_points_field_key( $points->station_id );
				$station_points_by_key[ $key ] = $points->points;
			}
		);

		$save_station_and_extra_points_button = sprintf(
			'
			<div class="tuja-buttons">
        		<button type="submit" class="button button-primary" name="tuja_points_action" value="%s">Spara stations- och bonuspoäng</button>
    		</div>',
			'save_stations_and_extras'
		);

		include 'views/group-score.php';
	}
}
