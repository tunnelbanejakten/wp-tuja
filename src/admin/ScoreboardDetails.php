<?php

namespace tuja\admin;

use tuja\data\model\Station;
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
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\store\StationDao;

class ScoreboardDetails extends Scoreboard {

	private $question_dao;
	private $question_group_dao;
	private $group_dao;
	private $group_category_dao;
	private $station_dao;

	public function __construct() {
		parent::__construct();
		$this->question_dao       = new QuestionDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->group_dao          = new GroupDao();
		$this->group_category_dao = new GroupCategoryDao();
		$this->station_dao        = new StationDao();
	}


	public function handle_post() {
	}

	private function create_row_values_mapper( array $score_board, string $objects_section, int $object_id ) {
		return function ( array $group_ids ) use ( $score_board, $objects_section, $object_id ) {
			$all_scores = array_values(
				array_map(
					function ( $obj ) use ( $objects_section, $object_id ) {
						return $obj['details']->{$objects_section}[ $object_id ]->final;
					},
					array_filter(
						$score_board,
						function ( $obj ) use ( $group_ids ) {
							return in_array( $obj['group_id'], $group_ids );
						}
					)
				)
			);
			if ( count( $all_scores ) > 1 ) {
				return sprintf( '%0.2f (medel)', array_sum( $all_scores ) / count( $all_scores ) );
				// return sprintf( '%s, avg: %0.2f', join( ', ', $all_scores ), array_sum( $all_scores ) / count( $all_scores ) );
			} else {
				return sprintf( '%0.2f', $all_scores[0] );
			}
		};
	}

	public function output() {
		$this->handle_post();

		$groups           = array_values(
			array_filter(
				$this->group_dao->get_all_in_competition( $this->competition->id ),
				function ( Group $group ) {
					return $group->get_status() !== Group::STATUS_DELETED;
				}
			)
		);
		$group_categories = $this->group_category_dao->get_all_in_competition( $this->competition->id );

		$competition = $this->competition;

		$unreviewed_answers = $this->get_unreviewed_answers_count();

		$score_board = $this->get_sorted_scoreboard();

		$aggregate_headers = array_merge(
			array(
				'Alla' => array_map(
					function ( Group $group ) {
						return $group->id;
					},
					$groups
				),
			),
			array_combine(
				array_map(
					function ( Group $group ) {
						return $group->name;
					},
					$groups
				),
				array_map(
					function ( Group $group ) {
						return array( $group->id );
					},
					$groups
				)
			)
		);

		$questions        = $this->question_dao->get_all_in_competition( $competition->id );
		$question_groups  = array_reduce(
			$this->question_group_dao->get_all_in_competition( $competition->id ),
			function ( array $res, QuestionGroup $qg ) {
				$res[ $qg->id ] = $qg->text;
				return $res;
			},
			array()
		);
		$questions_fields = array_map(
			function ( AbstractQuestion $question ) use ( $question_groups, $aggregate_headers, $score_board ) {
				return array(
					'label'          => $question->text,
					'question_group' => $question_groups[ $question->question_group_id ] ?? sprintf( 'Namnlös grupp %s', $question->question_group_id ),
					'fields'         => array_map(
						$this->create_row_values_mapper( $score_board, 'questions', $question->id ),
						array_values( $aggregate_headers )
					),
				);
			},
			$questions
		);
		usort(
			$questions_fields,
			function ( $questions_field_1, $questions_field_2 ) {
				return strcmp( $questions_field_1['question_group'], $questions_field_2['question_group'] );
			}
		);
		$stations        = $this->station_dao->get_all_in_competition( $this->competition->id );
		$stations_fields = array_map(
			function ( Station $station ) use ( $aggregate_headers, $score_board ) {
				return array(
					'label'  => $station->name,
					'fields' => array_map(
						$this->create_row_values_mapper( $score_board, 'stations', $station->id ),
						array_values( $aggregate_headers )
					),
				);
			},
			$stations
		);

		include 'views/scoreboard-details.php';
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
			$this->question_dao,
			$this->question_group_dao,
			new ResponseDao(),
			$this->group_dao,
			new QuestionPointsOverrideDao(),
			new StationPointsDao(),
			new EventDao()
		);
		$score_board = $calculator->score_board( true );
		usort(
			$score_board,
			function ( $a, $b ) {
				return $b['score'] - $a['score'];
			}
		);
		return $score_board;
	}
}
