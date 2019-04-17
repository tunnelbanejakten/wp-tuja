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

	public function score( $group_id, $account_for_group_max = false ) {
		$question_score = $this->score_per_question( $group_id );

		if ( $account_for_group_max ) {
			// TODO: Extract some of this code to separate function
			$sum_per_question_group = [];

			$question_group_map = [];
			foreach ( $this->questions as $question ) {
				$question_group_map[ $question->id ] = $question->question_group_id;
			}
			$question_group_max = [];
			foreach ( $this->question_groups as $question_group ) {
				$question_group_max[ $question_group->id ] = $question_group->score_max;
			}
			foreach ( $question_score as $question_id => $score ) {
				$question_group_id = $question_group_map[ $question_id ];
				if ( ! isset( $sum_per_question_group[ $question_group_id ] ) ) {
					$sum_per_question_group[ $question_group_id ] = 0;
				}
				$sum_per_question_group[ $question_group_id ] += $score;
			}

			$sum = 0;
			foreach ( $sum_per_question_group as $question_group_id => $group_sum ) {
				$sum += min( $group_sum, $question_group_max[ $question_group_id ] );
			}

			return $sum;
		} else {
			return array_sum( $question_score );
		}

	}

	/**
	 * Calculates total score for a single team.
	 */
	public function score_per_question( $group_id, $consider_overrides = true ) {
		$points_overrides = array();
		if ( $consider_overrides ) {
			$points           = $this->points_dao->get_by_group( $group_id );
			$points_overrides = array_combine( array_map( function ( $points ) {
				return $points->form_question_id;
			}, $points ), $points );
		}

		$scores    = [];
		$responses = $this->response_dao->get_latest_by_group( $group_id );

		foreach ( $this->questions as $question ) {
			if ( isset( $responses[ $question->id ] ) ) {
				$answers = $responses[ $question->id ]->answers;
				// TODO: How should the is_reviewed flag be used? Only count points for answers where is_reviewed = true?
				if ( isset( $answers ) ) {
					$scores[ $question->id ] = $question->score( $answers );
				}
			}
			if ( $consider_overrides
			     && isset( $points_overrides[ $question->id ] )
			     && isset( $responses[ $question->id ] )
			     && $points_overrides[ $question->id ]->created > $responses[ $question->id ]->created ) {
				$scores[ $question->id ] = $points_overrides[ $question->id ]->points;
			}
		}

		return $scores;
	}

	public function score_board() {
		$result = [];
		$groups = $this->group_dao->get_all_in_competition( $this->competition_id );
		foreach ( $groups as $group ) {
			$group_result = [];
			// TODO: Return proper objects instead of associative arrays.
			$group_result['group_id']   = $group->id;
			$group_result['group_name'] = $group->name;
			$group_result['score']      = $this->score( $group->id, true );
			$result[]                   = $group_result;
		}

		return $result;
	}
}