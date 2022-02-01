<?php

namespace tuja\data\store;

use tuja\util\Database;

class QuestionPointsOverrideDao extends AbstractPointsDao {

	function __construct() {
		parent::__construct(
			Database::get_table( 'form_question_points' ),
			'form_question_id'
		);
	}

	public function get_by_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return $this->to_points( $row );
			},
			'' .
			'SELECT p.* ' .
			'FROM ' . $this->table . ' p ' .
			'INNER JOIN ' . Database::get_table( 'form_question' ) . ' fq ON p.form_question_id = fq.id ' .
			'INNER JOIN ' . Database::get_table( 'form_question_group' ) . ' fqg ON fq.question_group_id = fqg.id ' .
			'INNER JOIN ' . Database::get_table( 'form' ) . ' f ON fqg.form_id = f.id ' .
			'WHERE f.competition_id = %d',
			$competition_id
		);
	}
}
