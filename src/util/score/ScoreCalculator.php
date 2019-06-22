<?php

namespace tuja\util\score;

use tuja\data\model\Question;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;

class ScoreCalculator
{
	private $competition_id;
	private $response_dao;
	private $group_dao;
	private $points_dao;
	private $question_groups;
	private $questions;

	public function __construct(
		$competition_id,
		QuestionDao $question_dao,
		QuestionGroupDao $question_group_dao,
		ResponseDao $response_dao,
		GroupDao $group_dao,
		PointsDao $points_dao
	) {
		$this->competition_id  = $competition_id;
		$this->response_dao    = $response_dao;
		$this->group_dao       = $group_dao;
		$this->points_dao      = $points_dao;
		$this->question_groups = $question_group_dao->get_all_in_competition( $competition_id );
		$this->questions       = $question_dao->get_all_in_competition( $competition_id );
	}

	public function score( $group_id ): ScoreResult {
		$result            = new ScoreResult();
		$result->questions = $this->score_per_question( $group_id );

		$result->total_final = $this->calculate_total_final( $result );

		$result->total_without_question_group_max_limits = $this->calculate_total_without_question_group_max_limits( $result );

		return $result;
	}

	private function calculate_total_without_question_group_max_limits( ScoreResult $result ) {
		return array_sum( array_map( function ( ScoreQuestionResult $question_result ) {
			return $question_result->final;
		}, $result->questions ) );
	}

	private function calculate_total_final( ScoreResult $result ) {
		$sum_per_question_group = [];

		// Start by mapping a question id to a question group id, for easy access.
		$question_group_map = [];
		foreach ( $this->questions as $question ) {
			$question_group_map[ $question->id ] = $question->question_group_id;
		}

		// ...and then map question group id to the maximum score per question group, for easy access.
		$question_group_max = [];
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

		return $sum;
	}

	private function score_per_question( $group_id ) {
		$points           = $this->points_dao->get_by_group( $group_id );
		$points_overrides = array_combine( array_map( function ( $points ) {
			return $points->form_question_id;
		}, $points ), $points );
		$scores    = [];
		$responses = $this->response_dao->get_latest_by_group( $group_id );

		foreach ( $this->questions as $question ) {
			$question_result = new ScoreQuestionResult();
			$response_exists = isset( $responses[ $question->id ] );
			if ( $response_exists ) {
				$submitted_answer = $responses[ $question->id ]->submitted_answer;
				// TODO: How should the is_reviewed flag be used? Only count points for answers where is_reviewed = true?
				if ( isset( $submitted_answer ) ) {
					$question_result->auto  = $question->score( $submitted_answer );
					$question_result->final = $question_result->auto;
				}
			}
			$override_exists                    = isset( $points_overrides[ $question->id ] );
			$override_set_after_latest_response = $response_exists &&
			                                      isset( $points_overrides[ $question->id ] ) &&
			                                      isset( $responses[ $question->id ]->created ) &&
			                                      $points_overrides[ $question->id ]->created > $responses[ $question->id ]->created;
			if ( $override_exists && ( ! $response_exists || $override_set_after_latest_response ) ) {
				$question_result->override = $points_overrides[ $question->id ]->points;
				$question_result->final    = $question_result->override;
			}
			$scores[ $question->id ] = $question_result;
		}

		return $scores;
	}

	public function score_board() {
		$result = [];
		$groups = $this->group_dao->get_all_in_competition( $this->competition_id );
		foreach ( $groups as $group ) {

			$score_result = $this->score( $group->id );

			$group_result = [];
			// TODO: Return proper objects instead of associative arrays.
			$group_result['group_id']   = $group->id;
			$group_result['group_name'] = $group->name;
			$group_result['score']      = $score_result->total_final;
			$result[]                   = $group_result;
		}

		return $result;
	}
}