<?php

namespace tuja\admin;

use tuja\data\model\Group;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\ResponseDao;
use tuja\data\store\EventDao;
use tuja\data\store\QuestionPointsOverrideDao;
use tuja\data\store\StationPointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\util\score\ScoreCalculator;

class Scoreboard extends AbstractCompetitionPage {

	public function handle_post() {
	}


	public function output() {
		$this->handle_post();

		$db_groups           = new GroupDao();
		$db_group_categories = new GroupCategoryDao();

		$groups           = array_values(
			array_filter(
				$db_groups->get_all_in_competition( $this->competition->id ),
				function ( Group $group ) {
					return $group->get_status() !== Group::STATUS_DELETED;
				}
			)
		);
		$group_categories = $db_group_categories->get_all_in_competition( $this->competition->id );

		$competition = $this->competition;

		$unreviewed_answers = $this->get_unreviewed_answers_count();

		$score_board = $this->get_sorted_scoreboard();

		$enriched_score_board = array_map(
			function ( $obj ) use ( $groups, $unreviewed_answers ) {
				$group_found = array_filter(
					$groups,
					function ( $group ) use ( $obj ) {
						return $group->id == $obj['group_id'];
					}
				);
				$group       = current( $group_found );

				$unreviewed_count = @$unreviewed_answers[ $group->id ] ?: 0;
				$unreviewed_link  = add_query_arg(
					array(
						'tuja_group' => $group->id,
						'tuja_view'  => 'GroupScore',
						\tuja\admin\GroupScore::QUESTION_FILTER_URL_PARAM => ResponseDao::QUESTION_FILTER_UNREVIEWED_ALL,
					)
				);

				$obj['unreviewed_count'] = $unreviewed_count;
				$obj['unreviewed_link']  = $unreviewed_link;
				$obj['category']         = $group->get_category();
				return $obj;
			},
			$score_board
		);

		$team_scores_by_category = array();
		foreach ( $enriched_score_board as $team_score ) {
			$key                               = $team_score['category'] ? $team_score['category']->name : 'Övriga';
			$team_scores_by_category[ $key ][] = $team_score;
		}

		include 'views/scoreboard.php';
	}

	private function get_unreviewed_answers_count(): array {
		$response_dao = new ResponseDao();
		$data         = $response_dao->get_by_questions(
			$this->competition->id,
			ResponseDao::QUESTION_FILTER_UNREVIEWED_ALL,
			array()
		);

		$unreviewed_answers = array();
		foreach ( $data as $form_entry ) {
			foreach ( $form_entry['questions'] as $question_entry ) {
				foreach ( $question_entry['responses'] as $response_entry ) {
					$response = isset( $response_entry ) ? $response_entry['response'] : null;
					if ( isset( $response ) ) {
						$unreviewed_answers[ $response->group_id ] = ( @$unreviewed_answers[ $response->group_id ] ?: 0 ) + 1;
					}
				}
			}
		}

		return $unreviewed_answers;
	}

	private function get_sorted_scoreboard() {
		$calculator  = new ScoreCalculator(
			$this->competition->id,
			new QuestionDao(),
			new QuestionGroupDao(),
			new ResponseDao(),
			new GroupDao(),
			new QuestionPointsOverrideDao(),
			new StationPointsDao(),
			new EventDao()
		);
		$score_board = $calculator->score_board();
		usort(
			$score_board,
			function ( $a, $b ) {
				return $b['score'] - $a['score'];
			}
		);
		return $score_board;
	}
}
