<?php

namespace tuja\util\score;

use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\Event;
use tuja\data\model\Group;
use tuja\data\model\Response;
use tuja\data\store\EventDao;
use tuja\data\store\ExtraPointsDao;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionPointsOverrideDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\data\store\StationDao;
use tuja\data\store\StationPointsDao;

class ScoreCalculator {

	const VIEW_EVENT_ERROR_MARGIN_SECONDS = 4;

	private $competition_id;
	private $response_dao;
	private $group_dao;
	private $question_points_override_dao;
	private $station_points_dao;
	private $extra_points_dao;
	private $event_dao;
	private $question_groups;
	private $questions;

	public function __construct(
		$competition_id,
		QuestionDao $question_dao,
		QuestionGroupDao $question_group_dao,
		ResponseDao $response_dao,
		GroupDao $group_dao,
		QuestionPointsOverrideDao $question_points_override_dao,
		StationPointsDao $station_points_dao,
		ExtraPointsDao $extra_points_dao,
		EventDao $event_dao
	) {
		$this->competition_id               = $competition_id;
		$this->response_dao                 = $response_dao;
		$this->group_dao                    = $group_dao;
		$this->question_points_override_dao = $question_points_override_dao;
		$this->station_points_dao           = $station_points_dao;
		$this->extra_points_dao             = $extra_points_dao;
		$this->event_dao                    = $event_dao;
		$this->question_groups              = $question_group_dao->get_all_in_competition( $competition_id );
		$this->questions                    = $question_dao->get_all_in_competition( $competition_id );
		$this->stations                     = ( new StationDao() )->get_all_in_competition( $competition_id );
	}

	public static function is_submitted_in_time( Response $response, AbstractQuestion $question, Group $group ) {
		$time_limit_adjusted = $question->get_adjusted_time_limit( $group );
		if ( $time_limit_adjusted > 0 && isset( $response->view_event_time_elapsed ) ) {
			$answer_time     = $response->view_event_time_elapsed;
			$max_answer_time = $time_limit_adjusted + self::VIEW_EVENT_ERROR_MARGIN_SECONDS;
			return $answer_time <= $max_answer_time;
		} else {
			return true;
		}
	}

	public static function score_combined( $response, AbstractQuestion $question, $override, Group $group, ?Event $first_view_event ): ScoreQuestionResult {
		$question_result = new ScoreQuestionResult();
		$response_exists = isset( $response );
		if ( $response_exists ) {
			$submitted_answer = $response->submitted_answer;
			// TODO: How should the is_reviewed flag be used? Only count points for answers where is_reviewed = true?
			if ( isset( $submitted_answer ) ) {
				$auto_score_result                = $question->score( $submitted_answer );
				$question_result->auto            = $auto_score_result->score;
				$question_result->auto_confidence = $auto_score_result->confidence;

				if ( ! self::is_submitted_in_time( $response, $question, $group ) ) {
					$question_result->auto            = 0;
					$question_result->auto_confidence = 1.0;
				}
				$question_result->final = $question_result->auto ?: 0;
			}
		}
		$override_exists                    = isset( $override );
		$override_set_after_latest_response = $response_exists &&
											  isset( $override ) &&
											  isset( $response->created ) &&
											  $override->created > $response->created;
		if ( $override_exists && ( ! $response_exists || $override_set_after_latest_response ) ) {
			$question_result->override = $override->points;
			$question_result->final    = $question_result->override ?: 0;
		}

		return $question_result;
	}

	public function score( Group $group ): ScoreResult {
		$result            = new ScoreResult();
		$result->questions = $this->score_per_question( $group );
		$result->stations  = $this->score_per_station( $group );
		$result->extra     = $this->score_per_extra( $group );

		$result->total_final = $this->calculate_total_final( $result );

		$result->total_without_question_group_max_limits = $this->calculate_total_without_question_group_max_limits( $result );

		return $result;
	}

	private function calculate_total_without_question_group_max_limits( ScoreResult $result ) {
		return array_sum(
			array_map(
				function ( ScoreQuestionResult $question_result ) {
					return $question_result->final;
				},
				array_merge(
					array_values( $result->questions ),
					array_values( $result->stations ),
					array_values( $result->extra )
				)
			)
		);
	}

	private function calculate_total_final( ScoreResult $result ) {
		$sum_per_question_group = array();

		// Start by mapping a question id to a question group id, for easy access.
		$question_group_map = array();
		foreach ( $this->questions as $question ) {
			$question_group_map[ $question->id ] = $question->question_group_id;
		}

		// ...and then map question group id to the maximum score per question group, for easy access.
		$question_group_max = array();
		foreach ( $this->question_groups as $question_group ) {
			$question_group_max[ $question_group->id ] = $question_group->score_max ?: PHP_INT_MAX;
		}

		// Count how many points the team has been awarded for each question group:
		foreach ( $result->questions as $question_id => $score ) {
			$question_group_id = $question_group_map[ $question_id ];
			if ( ! isset( $sum_per_question_group[ $question_group_id ] ) ) {
				$sum_per_question_group[ $question_group_id ] = 0;
			}
			$sum_per_question_group[ $question_group_id ] += $score->final;
		}

		// ...and then sum up all the points, taking into account the maximum number of points per question group:
		$sum = 0;
		foreach ( $sum_per_question_group as $question_group_id => $group_sum ) {
			$sum += min( $group_sum, $question_group_max[ $question_group_id ] );
		}

		// ...and then also add the points for all the stations.
		foreach ( $result->stations as $score ) {
			$sum += $score->final;
		}

		// ...and then also add the extra points
		foreach ( $result->extra as $score ) {
			$sum += $score->final;
		}

		return $sum;
	}

	private function get_view_question_events_by_question( $group_id ) {
		$all_view_question_events = array_filter(
			$this->event_dao->get_by_group( $group_id ),
			function ( Event $event ) {
				return $event->event_name === Event::EVENT_VIEW &&
					$event->object_type === Event::OBJECT_TYPE_QUESTION;
			}
		);
		$first_event_per_question = array_reduce(
			$all_view_question_events,
			function ( $res, Event $event ) {
				if ( ! isset( $res[ $event->object_id ] ) ) {
					$res[ $event->object_id ] = $event;
				}
				return $res;
			},
			array()
		);
		return $first_event_per_question;
	}

	private function score_per_question( Group $group ) {
		$group_id             = $group->id;
		$points               = $this->question_points_override_dao->get_by_group( $group_id );
		$points_overrides     = array_combine(
			array_map(
				function ( $points ) {
					return $points->form_question_id;
				},
				$points
			),
			$points
		);
		$scores               = array();
		$responses            = $this->response_dao->get_latest_by_group( $group_id );
		$view_question_events = $this->get_view_question_events_by_question( $group_id );

		foreach ( $this->questions as $question ) {
			$response                = @$responses[ $question->id ];
			$override                = @$points_overrides[ $question->id ];
			$first_view_event        = @$view_question_events[ $question->id ];
			$scores[ $question->id ] = self::score_combined(
				$response,
				$question,
				$override,
				$group,
				$first_view_event
			);
		}

		return $scores;
	}

	private function score_per_station( Group $group ) {
		$group_id         = $group->id;
		$points           = $this->station_points_dao->get_by_group( $group_id );
		$points_overrides = array_combine(
			array_map(
				function ( $points ) {
					return $points->station_id;
				},
				$points
			),
			$points
		);
		$scores           = array();

		foreach ( $this->stations as $station ) {
			$override       = @$points_overrides[ $station->id ];
			$station_result = new ScoreQuestionResult();

			$station_result->auto            = null;
			$station_result->auto_confidence = null;
			$station_result->override        = @$override->points ?? null;
			$station_result->final           = $station_result->override ?? 0;

			$scores[ $station->id ] = $station_result;
		}

		return $scores;
	}

	private function score_per_extra( Group $group ) {
		$group_id         = $group->id;
		$all_extra_points = $this->extra_points_dao->get_by_group( $group_id );
		$scores           = array();

		foreach ( $all_extra_points as $extra_points ) {
			$points_result = new ScoreQuestionResult();

			$points_result->auto            = null;
			$points_result->auto_confidence = null;
			$points_result->override        = null;
			$points_result->final           = $extra_points->points ?? 0;

			$scores[ $extra_points->name ] = $points_result;
		}

		return $scores;
	}

	public function score_board( $details = false ) {
		$result = array();
		$groups = $this->group_dao->get_all_in_competition( $this->competition_id );
		foreach ( $groups as $group ) {

			$score_result           = $this->score( $group );
			$count_scored_questions = array_filter(
				$score_result->questions,
				function ( $q ) {
					return ! is_null( $q->auto ) || ! is_null( $q->override );
				}
			);

			$group_result = array();
			// TODO: Return proper objects instead of associative arrays.
			$group_result['group_id']              = $group->id;
			$group_result['group_name']            = $group->name;
			$group_result['group_count_competing'] = $group->count_competing;
			$group_result['score']                 = $score_result->total_final;
			$group_result['progress']              = count( $count_scored_questions ) / count( $score_result->questions );
			if ( $details ) {
				$group_result['details'] = $score_result;
			}
			$result[] = $group_result;
		}

		return $result;
	}
}
