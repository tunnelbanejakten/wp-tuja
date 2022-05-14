<?php

namespace tuja\data\store;

use DateTime;
use Exception;
use tuja\data\model\Event;
use tuja\data\model\Group;
use tuja\data\model\Response;
use tuja\util\Database;
use tuja\util\score\ScoreCalculator;

class ResponseDao extends AbstractDao {

	const QUESTION_FILTER_ALL                       = 'all';
	const QUESTION_FILTER_IMAGES                    = 'images';
	const QUESTION_FILTER_UNREVIEWED_ALL            = 'unreviewed_all';
	const QUESTION_FILTER_UNREVIEWED_IMAGES         = 'unreviewed_images';
	const QUESTION_FILTER_UNREVIEWED_CHECKPOINT     = 'unreviewed_checkpoints';
	const QUESTION_FILTER_LOW_CONFIDENCE_AUTO_SCORE = 'low_confidence_auto_score';

	const QUESTION_FILTERS = array(
		self::QUESTION_FILTER_ALL                       => array(
			'sql_from'  => 'wp_tuja_form_question AS q LEFT JOIN wp_tuja_form_question_response AS r ON q.id = r.form_question_id',
			'sql_where' => array(),
		),
		self::QUESTION_FILTER_IMAGES                    => array(
			'sql_from'  => 'wp_tuja_form_question AS q LEFT JOIN wp_tuja_form_question_response AS r ON q.id = r.form_question_id',
			'sql_where' => array(
				'q.type = "' . QuestionDao::QUESTION_TYPE_IMAGES . '"',
			),
		),
		self::QUESTION_FILTER_UNREVIEWED_ALL            => array(
			'sql_from'  => 'wp_tuja_form_question_response AS r INNER JOIN wp_tuja_form_question AS q ON q.id = r.form_question_id',
			'sql_where' => array(
				'r.id IN (SELECT MAX(r2.id) FROM wp_tuja_form_question_response AS r2 GROUP BY r2.team_id, r2.form_question_id)',
				'r.is_reviewed = FALSE',
			),
		),
		self::QUESTION_FILTER_UNREVIEWED_IMAGES         => array(
			'sql_from'  => 'wp_tuja_form_question_response AS r INNER JOIN wp_tuja_form_question AS q ON q.id = r.form_question_id',
			'sql_where' => array(
				'r.id IN (SELECT MAX(r2.id) FROM wp_tuja_form_question_response AS r2 GROUP BY r2.team_id, r2.form_question_id)',
				'q.type = "' . QuestionDao::QUESTION_TYPE_IMAGES . '"',
				'r.is_reviewed = FALSE',
			),
		),
		self::QUESTION_FILTER_UNREVIEWED_CHECKPOINT     => array(
			'sql_from'  => 'wp_tuja_form_question_response AS r INNER JOIN wp_tuja_form_question AS q ON q.id = r.form_question_id',
			'sql_where' => array(
				'r.id IN (SELECT MAX(r2.id) FROM wp_tuja_form_question_response AS r2 GROUP BY r2.team_id, r2.form_question_id)',
				'EXISTS (SELECT m.id FROM wp_tuja_marker AS m WHERE m.link_form_question_id = q.id OR m.link_question_group_id = q.question_group_id)', // TODO: Doesn't account for whole forms linked to a marker, only questions and question groups.
				'r.is_reviewed = FALSE',
			),
		),
		self::QUESTION_FILTER_LOW_CONFIDENCE_AUTO_SCORE => array(
			'sql_from'                   => 'wp_tuja_form_question_response AS r INNER JOIN wp_tuja_form_question AS q ON q.id = r.form_question_id',
			'sql_where'                  => array(),
			'score_confidence_threshold' => 0.6,
		),
	);

	const TABLE_COLUMNS_RESPONSES = array(
		'id',
		'form_question_id',
		'team_id',
		'answer',
		'is_reviewed',
		'created_at',
	);

	const TABLE_COLUMNS_FORMS = array(
		'id',
		'random_id',
		'competition_id',
		'name',
		'allow_multiple_responses_per_team',
		'submit_response_start',
		'submit_response_end',
	);

	const TABLE_COLUMNS_QUESTIONS = array(
		'id',
		'form_id',
		'type',
		'name',
		'answer',
		'text',
		'sort_order',
		'limit_time',
		'text_hint',
		'random_id',
		'question_group_id',
	);

	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'form_question_response' );
	}

	function create( Response $response ) {
		$response->validate();

		$query_template = '
            INSERT INTO ' . $this->table . ' (
                form_question_id,
                team_id,
                answer,
                created_at
            ) VALUES (
                %d,
                %d,
                %s,
                %d
            )';

		$answer = json_encode( $response->submitted_answer );
		$lock   = self::to_db_date( new DateTime() );

		$result = $this->wpdb->query(
			$this->wpdb->prepare(
				$query_template,
				$response->form_question_id,
				$response->group_id,
				$answer,
				$lock
			)
		);

		if ( empty( $result ) ) {
			return false;
		}

		$response->created_at = $lock;
		return $response;
	}

	function get_latest_by_group( $group_id ) {
		$latest_responses = array();
		$all_responses    = $this->get_objects(
			function ( $row ) {
				return self::to_response( $row );
			},
			'
			SELECT
				r.*,
				r.created_at - (
					SELECT MIN(evt.created_at) 
					FROM ' . Database::get_table( 'event' ) . ' evt 
					WHERE 
						evt.team_id = r.team_id AND 
						evt.object_id = r.form_question_id AND 
						evt.object_type = "' . Event::OBJECT_TYPE_QUESTION . '" AND 
						evt.event_name = "' . Event::EVENT_VIEW . '"
				) AS view_event_time_elapsed
			FROM ' . $this->table . ' AS r
			WHERE
				r.team_id = %d
				AND r.id IN (
					SELECT MAX(r2.id) 
					FROM ' . Database::get_table( 'form_question_response' ) . ' AS r2 
					WHERE r2.team_id = %d
					GROUP BY r2.form_question_id
				)
			ORDER BY r.id',
			$group_id,
			$group_id
		);

		foreach ( $all_responses as $response ) {
			$latest_responses[ $response->form_question_id ] = $response;
		}

		return $latest_responses;
	}

	function mark_as_reviewed( array $response_ids ) {
		if ( count( $response_ids ) == 0 ) {
			return true;
		}
		$ids           = join( ', ', array_map( 'intval', array_filter( $response_ids, 'is_numeric' ) ) );
		$query         = sprintf( 'UPDATE ' . $this->table . ' SET is_reviewed = TRUE WHERE id IN (%s)', $ids );
		$affected_rows = $this->wpdb->query( $query );

		return $affected_rows === count( $response_ids );
	}

	function get_by_questions( $competition_id, $question_filter, array $group_ids ) {
		$map_and_join = function ( string $map_pattern, array $items ) {
			return join(
				',',
				array_map(
					function ( $column_name ) use ( $map_pattern ) {
						return sprintf( $map_pattern, $column_name, $column_name );
					},
					$items
				)
			);
		};

		$where_conditions = array_merge(
			self::QUESTION_FILTERS[ $question_filter ]['sql_where'],
			count( $group_ids ) > 0
				? array(
					sprintf(
						'(r.team_id IS NULL OR r.team_id IN (%s))',
						join( ', ', $group_ids )
					),
				)
				: array(),
			array( 'f.competition_id = ' . (int) $competition_id )
		);

		$query = sprintf(
			'
			SELECT 
				' . $map_and_join( 'q.%s AS q_%s', self::TABLE_COLUMNS_QUESTIONS ) . ',
				' . $map_and_join( 'r.%s AS r_%s', self::TABLE_COLUMNS_RESPONSES ) . ',
				' . $map_and_join( 'f.%s AS f_%s', self::TABLE_COLUMNS_FORMS ) . ',
				r.created_at - (
					SELECT MIN(evt.created_at) 
					FROM ' . Database::get_table( 'event' ) . ' evt 
					WHERE 
						evt.team_id = r.team_id AND 
						evt.object_id = r.form_question_id AND 
						evt.object_type = "' . Event::OBJECT_TYPE_QUESTION . '" AND 
						evt.event_name = "' . Event::EVENT_VIEW . '"
				) AS r_view_event_time_elapsed
			FROM 
				%s
				INNER JOIN ' . Database::get_table( 'form_question_group' ) . ' fqg ON q.question_group_id = fqg.id 
				INNER JOIN ' . Database::get_table( 'form' ) . ' f ON fqg.form_id = f.id 
			WHERE 
				%s
			ORDER BY
				f.id, q.id, r.team_id, r.id
			',
			self::QUESTION_FILTERS[ $question_filter ]['sql_from'],
			join( ' AND ', $where_conditions ),
		);

		// CONVERT RAW DATA TO OBJECTS

		$entries = $this->get_objects(
			function ( $row ) {
				$question = self::to_form_question(
					(object) array_combine(
						self::TABLE_COLUMNS_QUESTIONS,
						array_map(
							function ( $column_name ) use ( $row ) {
								return $row->{'q_' . $column_name};
							},
							self::TABLE_COLUMNS_QUESTIONS
						)
					)
				);

				$response = $row->r_id != null ? self::to_response(
					(object) array_combine(
						array_merge( self::TABLE_COLUMNS_RESPONSES, array( 'view_event_time_elapsed' ) ),
						array_map(
							function ( $column_name ) use ( $row ) {
								return $row->{'r_' . $column_name};
							},
							array_merge( self::TABLE_COLUMNS_RESPONSES, array( 'view_event_time_elapsed' ) )
						)
					)
				) : null;

				$form = self::to_form(
					(object) array_combine(
						self::TABLE_COLUMNS_FORMS,
						array_map(
							function ( $column_name ) use ( $row ) {
								return $row->{'f_' . $column_name};
							},
							self::TABLE_COLUMNS_FORMS
						)
					)
				);

				return array(
					'question' => $question,
					'response' => $response,
					'form'     => $form,
				);
			},
			$query
		);

		$result = array();

		// GROUP OBJECTS

		$groups    = null;
		$get_group = function ( int $group_id ) use ( &$groups, $competition_id ): Group {
			if ( ! isset( $groups ) ) {
				$groups     = array();
				$all_groups = ( new GroupDao() )->get_all_in_competition( (int) $competition_id, true );
				array_walk(
					$all_groups,
					function ( Group $group ) use ( &$groups ) {
						$groups[ $group->id ] = $group;
					}
				);
			}
			return $groups[ $group_id ];
		};

		foreach ( $entries as $entry ) {
			$data = null;
			if ( isset( $entry['response'] ) ) {
				$consider_response = true;
				if ( $entry['question']->is_timed() ) {
					$consider_response = ScoreCalculator::is_submitted_in_time(
						$entry['response'],
						$entry['question'],
						$get_group( $entry['response']->group_id )
					);
				}
				if ( $consider_response ) {
					$score = $entry['question']->score( $entry['question']->get_answer_object( 'dummy', $entry['response']->submitted_answer, new Group() ) );

					$score_confidence_threshold = @self::QUESTION_FILTERS[ $question_filter ]['score_confidence_threshold'] ?: 1.0;
					if ( $score->confidence <= $score_confidence_threshold ) {
						$data = array(
							'response' => $entry['response'],
						);
					} else {
						continue;
					}
				}
			}

			if ( ! isset( $result[ $entry['form']->id ] ) ) {
				$result[ $entry['form']->id ] = array(
					'form'      => $entry['form'],
					'questions' => array(),
				);
			}

			$questions = &$result[ $entry['form']->id ]['questions'];
			if ( ! isset( $questions[ $entry['question']->id ] ) ) {
				$questions[ $entry['question']->id ] = array(
					'question'  => $entry['question'],
					'responses' => array(),
				);
			}

			if ( isset( $data ) ) {
				$group_id = $entry['response']->group_id;

				$questions[ $entry['question']->id ]['responses'][ $group_id ] = $data;
			}
		}

		return $result;
	}

	private static function to_response( $result ): Response {
		$r                          = new Response();
		$r->id                      = $result->id;
		$r->form_question_id        = $result->form_question_id;
		$r->group_id                = $result->team_id;
		$r->submitted_answer        = json_decode( $result->answer, true );
		$r->created                 = self::from_db_date( $result->created_at );
		$r->is_reviewed             = $result->is_reviewed;
		$r->view_event_time_elapsed = $result->view_event_time_elapsed;

		return $r;
	}

}
