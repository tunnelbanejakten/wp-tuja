<?php

namespace tuja\data\store;

use DateTime;
use tuja\data\model\Response;
use tuja\util\Database;

class ResponseDao extends AbstractDao
{
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

		$answer = json_encode( $response->answers );
		$lock = self::to_db_date(new DateTime());

		$result = $this->wpdb->query( $this->wpdb->prepare( $query_template, $response->form_question_id, $response->group_id, $answer, $lock ) );

		if(empty($result)) {
			return false;
		}

		$response->created_at = $lock;
		return $response;
	}

	public function get($group_id, $question_id, $latest = false) {
		$query = 'SELECT * FROM ' . $this->table . ' WHERE team_id = %d AND form_question_id = %d ORDER BY id';
		if($latest) {
			$query .= ' DESC LIMIT 1';
		}

		return $this->get_objects(
			function ( $row ) {
				return self::to_response( $row );
			},
			$query,
			$group_id,
			$question_id
		);
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
			'INNER JOIN ' . Database::get_table( 'form_question' ) . ' fq ON r.form_question_id = fq.id ' .
			'INNER JOIN ' . Database::get_table( 'form' ) . ' f ON (fq.form_id = f.id AND f.competition_id = %d) ' .
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
		$ids           = join( ', ', array_map( 'intval', array_filter( $response_ids, 'is_numeric' ) ) );
		$query         = sprintf( 'UPDATE ' . $this->table . ' SET is_reviewed = TRUE WHERE id IN (%s)', $ids );
		$affected_rows = $this->wpdb->query( $query );

		return $affected_rows === count( $ids );
	}

	protected static function to_response( $result ): Response {
		$r                   = new Response();
		$r->id               = $result->id;
		$r->form_question_id = $result->form_question_id;
		$r->group_id         = $result->team_id;
		$r->answers          = json_decode( $result->answer );
		$r->created          = self::from_db_date( $result->created_at );
		$r->is_reviewed      = $result->is_reviewed;

		return $r;
	}

}