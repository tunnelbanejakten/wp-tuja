<?php

namespace tuja\data\store;

use tuja\data\model\Form;
use tuja\util\Database;

class FormDao extends AbstractDao {

	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'form' );
	}

	function create( Form $form ) {
		$form->validate();

		$affected_rows = $this->wpdb->insert( $this->table,
			array(
				'competition_id'                    => $form->competition_id,
				'random_id'                         => $this->id->random_string(),
				'name'                              => $form->name,
				'allow_multiple_responses_per_team' => 1,
				// submit_response_start_effective is read-only.
				'submit_response_start'             => self::to_db_date( $form->submit_response_start ),
				// submit_response_end_effective is read-only.
				'submit_response_end'               => self::to_db_date( $form->submit_response_end )
			),
			array(
				'%d',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d'
			) );
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	function update( Form $form ) {
		$form->validate();

		return $this->wpdb->update( $this->table,
			array(
				'name'                  => $form->name,
				// submit_response_start_effective is read-only.
				'submit_response_start' => self::to_db_date( $form->submit_response_start ),
				// submit_response_end_effective is read-only.
				'submit_response_end'   => self::to_db_date( $form->submit_response_end )
			),
			array(
				'id' => $form->id
			) );
	}

	function get( $id ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_form( $row );
			},
			$this->generate_query('f.id = %d'),
			$id );
	}

	public function get_by_key( $key ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_form( $row );
			},
			$this->generate_query('f.random_id = %s'),
			$key );
	}

	function get_all_in_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_form( $row );
			},
			$this->generate_query('f.competition_id = %d'),
			$competition_id );
	}

	private function generate_query( String $where ) {
		return sprintf('
			SELECT 
				f.*,
				COALESCE(f.submit_response_start, c.event_start) AS submit_response_start_effective,
				COALESCE(f.submit_response_end, c.event_end) AS submit_response_end_effective
			FROM 
				' . $this->table . ' AS f 
				INNER JOIN ' . Database::get_table('competition') . ' AS c 
					ON f.competition_id = c.id
			WHERE
				%s', $where);
	}
}