<?php

namespace tuja\admin;

use tuja\data\model\Station;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
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
use tuja\data\store\ExtraPointsDao;
use tuja\data\store\StationDao;

class ScoreboardDetails extends Scoreboard {

	private $question_dao;
	private $question_group_dao;
	private $group_dao;
	private $group_category_dao;
	private $extra_points_dao;
	private $station_dao;
	private $score_board;
	private $column_definitions;

	public function __construct() {
		parent::__construct();
		$this->question_dao       = new QuestionDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->group_dao          = new GroupDao();
		$this->group_category_dao = new GroupCategoryDao();
		$this->station_dao        = new StationDao();
		$this->extra_points_dao   = new ExtraPointsDao();
	}

	private function get_column_definitions() {
		if ( ! isset( $this->column_definitions ) ) {
			$groups = array_values(
				array_filter(
					$this->group_dao->get_all_in_competition( $this->competition->id ),
					function ( Group $group ) {
						return $group->get_status() !== Group::STATUS_DELETED;
					}
				)
			);

			$group_categories = $this->group_category_dao->get_all_in_competition( $this->competition->id );

			$all_groups_column_definition = array(
				array(
					'label'     => 'Alla',
					'group_ids' => array_map(
						function ( Group $group ) {
							return $group->id;
						},
						$groups
					),
				),
			);

			$group_categories_columns_definitions = array_map(
				function ( GroupCategory $group_category ) use ( $groups ) {
					return array(
						'label'     => $group_category->name,
						'group_ids' => array_map(
							function ( Group $group ) {
								return $group->id;
							},
							array_filter(
								$groups,
								function ( Group $group ) use ( $group_category ) {
									return $group->category_id === $group_category->id;
								}
							)
						),
					);
				},
				$group_categories
			);

			$group_columns_definitions = array_map(
				function ( Group $group ) {
					return array(
						'label'     => $group->name,
						'group_ids' => array( $group->id ),
					);
				},
				$groups
			);

			$this->column_definitions = array_merge(
				$all_groups_column_definition,
				$group_categories_columns_definitions,
				$group_columns_definitions
			);
		}
		return $this->column_definitions;
	}

	private function get_scoreboard() {
		if ( ! isset( $this->score_board ) ) {
			$calculator        = new ScoreCalculator(
				$this->competition->id,
				$this->question_dao,
				$this->question_group_dao,
				new ResponseDao(),
				$this->group_dao,
				new QuestionPointsOverrideDao(),
				new StationPointsDao(),
				$this->extra_points_dao,
				new EventDao()
			);
			$this->score_board = $calculator->score_board( true );
		}
		return $this->score_board;
	}

	private function create_details_value_extrator( string $objects_section, $object_id ) {
		return function ( $obj ) use ( $objects_section, $object_id ) {
			return @$obj['details']->{$objects_section}[ $object_id ]->final ?? 0;
		};
	}

	private function create_overall_value_extrator( string $objects_section ) {
		return function ( $obj ) use ( $objects_section ) {
			return $obj['details']->{$objects_section};
		};
	}

	private function create_total_score_reduction_value_extrator() {
		return function ( $obj ) {
			return $obj['details']->total_without_question_group_max_limits - $obj['details']->total_final;
		};
	}

	private function create_overall_per_participant_value_extrator() {
		return function ( $obj ) {
			if ( 0 === $obj['group_count_competing'] ) {
				return '-';
			}
			return $obj['details']->total_final / $obj['group_count_competing'];
		};
	}

	private function create_row_values_mapper( array $score_board, $value_extractor ) {
		return function ( array $group_ids ) use ( $score_board, $value_extractor ) {
			$all_scores = array_values(
				array_map(
					$value_extractor,
					array_filter(
						$score_board,
						function ( $obj ) use ( $group_ids ) {
							return in_array( $obj['group_id'], $group_ids );
						}
					)
				)
			);
			$is_average = count( $group_ids ) > 1;
			$decimals   = $is_average ? 1 : 0;
			if ( count( $all_scores ) > 1 ) {
				$value = array_sum( $all_scores ) / count( $all_scores );
				return number_format( $value, $decimals, ',', '' );
			} elseif ( count( $all_scores ) === 1 ) {
				$value = floatval( $all_scores[0] );
				return number_format( $value, $decimals, ',', '' );
			} else {
				return '-';
			}
		};
	}

	private function get_overall_rows() {
		$column_group_ids = $this->get_column_group_ids();
		$score_board      = $this->get_scoreboard();
		return array(
			array(
				'label'  => 'Slutpoäng',
				'fields' => array_map(
					$this->create_row_values_mapper( $score_board, $this->create_overall_value_extrator( 'total_final' ) ),
					$column_group_ids
				),
			),
			array(
				'label'  => 'Slutpoäng per tävlande',
				'fields' => array_map(
					$this->create_row_values_mapper( $score_board, $this->create_overall_per_participant_value_extrator() ),
					$column_group_ids
				),
			),
			array(
				'label'  => 'Slutpoäng utan frågegruppsgränser',
				'fields' => array_map(
					$this->create_row_values_mapper( $score_board, $this->create_overall_value_extrator( 'total_without_question_group_max_limits' ) ),
					$column_group_ids
				),
			),
			array(
				'label'  => 'Avdrag pga. frågegruppsgränser',
				'fields' => array_map(
					$this->create_row_values_mapper( $score_board, $this->create_total_score_reduction_value_extrator() ),
					$column_group_ids
				),
			),
		);
	}

	private function get_question_rows() {
		$column_group_ids = $this->get_column_group_ids();
		$score_board      = $this->get_scoreboard();

		$questions       = $this->question_dao->get_all_in_competition( $this->competition->id );
		$question_groups = array_reduce(
			$this->question_group_dao->get_all_in_competition( $this->competition->id ),
			function ( array $res, QuestionGroup $qg ) {
				$res[ $qg->id ] = $qg->text;
				return $res;
			},
			array()
		);

		$questions_fields = array_map(
			function ( AbstractQuestion $question ) use ( $question_groups, $column_group_ids, $score_board ) {
				return array(
					'label'          => $question->text,
					'question_group' => $question_groups[ $question->question_group_id ] ?? sprintf( 'Namnlös grupp %s', $question->question_group_id ),
					'fields'         => array_map(
						$this->create_row_values_mapper( $score_board, $this->create_details_value_extrator( 'questions', $question->id ) ),
						$column_group_ids
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
		return $questions_fields;
	}

	private function get_station_rows() {
		$column_group_ids = $this->get_column_group_ids();
		$score_board      = $this->get_scoreboard();

		$stations = $this->station_dao->get_all_in_competition( $this->competition->id );
		return array_map(
			function ( Station $station ) use ( $column_group_ids, $score_board ) {
				return array(
					'label'  => $station->name,
					'fields' => array_map(
						$this->create_row_values_mapper( $score_board, $this->create_details_value_extrator( 'stations', $station->id ) ),
						$column_group_ids
					),
				);
			},
			$stations
		);

	}

	private function get_extra_rows() {
		$column_group_ids = $this->get_column_group_ids();
		$score_board      = $this->get_scoreboard();

		$extra_names = $this->extra_points_dao->all_names( $this->competition->id );
		return array_map(
			function ( string $name ) use ( $column_group_ids, $score_board ) {
				return array(
					'label'  => $name,
					'fields' => array_map(
						$this->create_row_values_mapper( $score_board, $this->create_details_value_extrator( 'extra', $name ) ),
						$column_group_ids
					),
				);
			},
			$extra_names
		);

	}

	private function get_column_group_ids() {
		return array_map(
			function ( array $column_definition ) {
				return $column_definition['group_ids'];
			},
			$this->get_column_definitions()
		);
	}

	public function output() {
		$competition = $this->competition;

		$column_labels = array_map(
			function ( array $column_definition ) {
				return $column_definition['label'];
			},
			$this->get_column_definitions()
		);

		$overall_fields   = $this->get_overall_rows();
		$questions_fields = $this->get_question_rows();
		$stations_fields  = $this->get_station_rows();
		$extras_fields    = $this->get_extra_rows();

		$stations_points_url = add_query_arg(
			array(
				'tuja_competition' => $this->competition->id,
				'tuja_view'        => 'StationsPoints',
			)
		);

		$extra_points_url = add_query_arg(
			array(
				'tuja_competition' => $this->competition->id,
				'tuja_view'        => 'ExtraPoints',
			)
		);

		include 'views/scoreboard-details.php';
	}
}
