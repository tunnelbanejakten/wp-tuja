<?php

namespace tuja\data\store;

use DateTime;
use tuja\data\model\Response;
use tuja\util\Database;

class ResponseDao extends AbstractDao
{
	const QUESTION_FILTER_ALL = 'all';
	const QUESTION_FILTER_IMAGES = 'images';
	const QUESTION_FILTER_UNREVIEWED_ALL = 'unreviewed_all';
	const QUESTION_FILTER_UNREVIEWED_IMAGES = 'unreviewed_images';
	const QUESTION_FILTER_LOW_CONFIDENCE_AUTO_SCORE = 'low_confidence_auto_score';

	const QUESTION_FILTERS = [
		self::QUESTION_FILTER_ALL                       => [
			'sql_from'  => 'wp_tuja_form_question AS q LEFT JOIN wp_tuja_form_question_response AS r ON q.id = r.form_question_id',
			'sql_where' => [
				'(r.id IS NULL OR r.id IN (SELECT MAX(latest.id) FROM wp_tuja_form_question_response AS latest WHERE latest.team_id = r.team_id AND latest.form_question_id = r.form_question_id))'
			]
		],
		self::QUESTION_FILTER_IMAGES                       => [
			'sql_from'  => 'wp_tuja_form_question AS q LEFT JOIN wp_tuja_form_question_response AS r ON q.id = r.form_question_id',
			'sql_where' => [
				'q.type = "' . QuestionDao::QUESTION_TYPE_IMAGES . '"',
				'(r.id IS NULL OR r.id IN (SELECT MAX(latest.id) FROM wp_tuja_form_question_response AS latest WHERE latest.team_id = r.team_id AND latest.form_question_id = r.form_question_id))'
			]
		],
		self::QUESTION_FILTER_UNREVIEWED_ALL            => [
			'sql_from'  => 'wp_tuja_form_question_response AS r INNER JOIN wp_tuja_form_question AS q ON q.id = r.form_question_id',
			'sql_where' => [
				'r.id IN (SELECT MAX(latest.id) FROM wp_tuja_form_question_response AS latest WHERE latest.team_id = r.team_id AND latest.form_question_id = r.form_question_id)',
				'r.is_reviewed = FALSE'
			]
		],
		self::QUESTION_FILTER_UNREVIEWED_IMAGES         => [
			'sql_from'  => 'wp_tuja_form_question_response AS r INNER JOIN wp_tuja_form_question AS q ON q.id = r.form_question_id',
			'sql_where' => [
				'q.type = "' . QuestionDao::QUESTION_TYPE_IMAGES . '"',
				'r.id IN (SELECT MAX(latest.id) FROM wp_tuja_form_question_response AS latest WHERE latest.team_id = r.team_id AND latest.form_question_id = r.form_question_id)',
				'r.is_reviewed = FALSE'
			]
		],
		self::QUESTION_FILTER_LOW_CONFIDENCE_AUTO_SCORE => [
			'sql_from'                   => 'wp_tuja_form_question_response AS r INNER JOIN wp_tuja_form_question AS q ON q.id = r.form_question_id',
			'sql_where'                  => [
				'r.id IN (SELECT MAX(latest.id) FROM wp_tuja_form_question_response AS latest WHERE latest.team_id = r.team_id AND latest.form_question_id = r.form_question_id)'
			],
			'score_confidence_threshold' => 0.6
		]
	];

	const TABLE_COLUMNS_RESPONSES = [
		'id',
		'form_question_id',
		'team_id',
		'answer',
		'is_reviewed',
		'created_at'
	];

	const TABLE_COLUMNS_FORMS = [
		'id',
		'competition_id',
		'name',
		'allow_multiple_responses_per_team',
		'submit_response_start',
		'submit_response_end'
	];

	const TABLE_COLUMNS_QUESTIONS = [
		'id',
		'form_id',
		'type',
		'answer',
		'text',
		'sort_order',
		'text_hint',
		'random_id',
		'question_group_id'
	];

	function __construct() {
		parent::__construct();
		$this->table = Database::get_table('form_question_response');
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
		$lock   = self::to_db_date(new DateTime());

		$result = $this->wpdb->query( $this->wpdb->prepare(
			$query_template,
			$response->form_question_id,
			$response->group_id,
			$answer,
			$lock ) );

		if(empty($result)) {
			return false;
		}

		$response->created_at = $lock;
		return $response;
	}

	public function get($group_id, $question_id = 0, $latest = false) {
		$query = 'SELECT * FROM ' . $this->table . ' WHERE team_id = %d AND form_question_id ' . ($question_id ? '=' : '!=') . ' %d ORDER BY id';

		if($latest) {
			$query .= ' DESC LIMIT 1';
		}

		$responses = $this->get_objects(
			function ( $row ) {
				return self::to_response( $row );
			},
			$query,
			$group_id,
			$question_id
		);

		if($latest) {
			return end($responses);
		}

		return $responses;
	}

	function get_by_group( $group_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_response( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE team_id = %d ORDER BY id',
			$group_id );
	}

	function get_latest_by_group( $group_id ) {
		$latest_responses = [];
		$all_responses    = $this->get_by_group( $group_id );
		foreach ( $all_responses as $response ) {
			$latest_responses[ $response->form_question_id ] = $response;
		}

		return $latest_responses;
	}

	function get_not_reviewed( $competition_id ) {
		$all_responses = $this->get_objects(
			function ( $row ) {
				return self::to_response( $row );
			},
			'SELECT r.* ' .
			'FROM ' . $this->table . ' r ' .
			'  INNER JOIN ' . Database::get_table( 'form_question' ) . ' fq ON r.form_question_id = fq.id ' .
			'  INNER JOIN ' . Database::get_table( 'form_question_group' ) . ' fqg ON fq.question_group_id = fqg.id ' .
			'  INNER JOIN ' . Database::get_table( 'form' ) . ' f ON fqg.form_id = f.id ' .
			'WHERE f.competition_id = %d ' .
			'ORDER BY r.id',
			$competition_id );

		$latest_responses = [];
		foreach ( $all_responses as $response ) {
			$latest_responses[ $response->form_question_id . '__' . $response->group_id ] = $response;
		}

		return array_filter( $latest_responses, function ( $response ) {
			return ! $response->is_reviewed;
		} );
	}

	function mark_as_reviewed( array $response_ids ) {
		if ( count( $response_ids ) == 0 ) {
			return true;
		}
		$ids           = join( ', ', array_map( 'intval', array_filter( $response_ids, 'is_numeric' ) ) );
		$query         = sprintf( 'UPDATE ' . $this->table . ' SET is_reviewed = TRUE WHERE id IN (%s)', $ids );
		$affected_rows = $this->wpdb->query( $query );

		return $affected_rows === count( $ids );
	}

	function get_by_questions( $competition_id, $question_filter, array $group_ids ) {
		$query = sprintf(
			'
			SELECT 
				%s, %s, %s
			FROM 
				%s
				INNER JOIN ' . Database::get_table( 'form_question_group' ) . ' fqg ON q.question_group_id = fqg.id 
				INNER JOIN ' . Database::get_table( 'form' ) . ' f ON fqg.form_id = f.id 
			WHERE 
				%s
				%s
				AND f.competition_id = %%d
			ORDER BY
				f.id, q.id, r.team_id
			',
			join( ',', array_map( function ( $column_name ) {
				return sprintf( 'q.%s AS q_%s', $column_name, $column_name );
			}, self::TABLE_COLUMNS_QUESTIONS ) ),
			join( ',', array_map( function ( $column_name ) {
				return sprintf( 'r.%s AS r_%s', $column_name, $column_name );
			}, self::TABLE_COLUMNS_RESPONSES ) ),
			join( ',', array_map( function ( $column_name ) {
				return sprintf( 'f.%s AS f_%s', $column_name, $column_name );
			}, self::TABLE_COLUMNS_FORMS ) ),
			self::QUESTION_FILTERS[ $question_filter ]['sql_from'],
			join( ' AND ', self::QUESTION_FILTERS[ $question_filter ]['sql_where'] ),
			count( $group_ids ) > 0 ? sprintf( ' AND r.team_id IN (%s)', join( ', ', $group_ids ) ) : ''
		);

		// CONVERT RAW DATA TO OBJECTS

		$entries = $this->get_objects(
			function ( $row ) {
				$question = self::to_form_question(
						(object) array_combine(
							self::TABLE_COLUMNS_QUESTIONS,
							array_map( function ( $column_name ) use ( $row ) {
								return $row->{'q_' . $column_name};
							}, self::TABLE_COLUMNS_QUESTIONS )
						)
				);

				$response = $row->r_id != null ? self::to_response(
						(object) array_combine(
							self::TABLE_COLUMNS_RESPONSES,
							array_map( function ( $column_name ) use ( $row ) {
								return $row->{'r_' . $column_name};
							}, self::TABLE_COLUMNS_RESPONSES )
						)
				) : null;

				$form = self::to_form(
						(object) array_combine(
							self::TABLE_COLUMNS_FORMS,
							array_map( function ( $column_name ) use ( $row ) {
								return $row->{'f_' . $column_name};
							}, self::TABLE_COLUMNS_FORMS )
						)
				);

				return [
					'question' => $question,
					'response' => $response,
					'form'     => $form
				];
			},
			$query,
			(int) $competition_id);

		$result = [];

		// GROUP OBJECTS

		foreach ( $entries as $entry ) {
			$data = null;
			if ( isset( $entry['response'] ) ) {

				$score = $entry['question']->score( $entry['response']->submitted_answer );

				$score_confidence_threshold = @self::QUESTION_FILTERS[ $question_filter ]['score_confidence_threshold'] ?: 1.0;
				if ( $score->confidence <= $score_confidence_threshold ) {
					$data = [
						'response' => $entry['response']
					];
				} else {
					continue;
				}
			}

			if ( ! isset( $result[ $entry['form']->id ] ) ) {
				$result[ $entry['form']->id ] = [
					'form'      => $entry['form'],
					'questions' => []
				];
			}

			$questions = &$result[ $entry['form']->id ]['questions'];
			if ( ! isset( $questions[ $entry['question']->id ] ) ) {
				$questions[ $entry['question']->id ] = [
					'question'  => $entry['question'],
					'responses' => []
				];
			}

			if ( isset( $data ) ) {
				$group_id = $entry['response']->group_id;

				$questions[ $entry['question']->id ]['responses'][ $group_id ] = $data;
			}
		}

		return $result;
	}

	private static function to_response( $result ): Response {
		$r                   = new Response();
		$r->id               = $result->id;
		$r->form_question_id = $result->form_question_id;
		$r->group_id         = $result->team_id;
		$r->submitted_answer = json_decode( $result->answer, true );
		$r->created          = self::from_db_date( $result->created_at );
		$r->is_reviewed      = $result->is_reviewed;

		return $r;
	}

}