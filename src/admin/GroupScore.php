<?php

namespace tuja\admin;

use Exception;
use tuja\data\store\EventDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MessageDao;
use tuja\data\store\QuestionPointsOverrideDao;
use tuja\data\store\StationPointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\data\store\StationDao;
use tuja\util\score\ScoreCalculator;

class GroupScore extends AbstractGroup {

	const DEFAULT_QUESTION_FILTER   = ResponseDao::QUESTION_FILTER_ALL;
	const QUESTION_FILTER_URL_PARAM = 'tuja_group_question_filter';

	private $review_component;

	public function __construct() {
		parent::__construct();
		$this->review_component = new ReviewComponent( $this->competition );
	}

	public function get_scripts(): array {
		return array(
			'admin-review-component.js'
		);
	}

	public function handle_post() {
		global $wpdb;

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
		}
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
		$db_station_points = new StationPointsDao();
		$db_stations       = new StationDao();
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
			$db_event
		);
		$score_result     = $score_calculator->score( $group );

		$responses             = $db_response->get_latest_by_group( $group->id );
		$response_per_question = array_combine(
			array_map(
				function ( $response ) {
					return $response->form_question_id;
				},
				$responses
			),
			array_values( $responses )
		);
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

		$back_url = add_query_arg(
			array(
				'tuja_competition' => $competition->id,
				'tuja_view'        => 'Groups',
			)
		);

		include 'views/group-score.php';
	}
}
