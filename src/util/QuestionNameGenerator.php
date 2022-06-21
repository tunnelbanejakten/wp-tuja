<?php

namespace tuja\util;

use Exception;
use tuja\data\model\QuestionGroup;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;

class QuestionNameGenerator {
	private int $competition_id;

	public static function update_competition_questions( int $competition_id ) {
		try {
			$name_generator = new QuestionNameGenerator( $competition_id );
			$name_generator->update();
			return true;
		} catch ( Exception $e ) {
			return false;
		}

	}

	function __construct( int $competition_id ) {
		$this->competition_id = $competition_id;
	}

	public function update() {
		$questions_dao       = new QuestionDao();
		$question_groups_dao = new QuestionGroupDao();

		$questions        = $questions_dao->get_all_in_competition( $this->competition_id );
		$question_groups  = $question_groups_dao->get_all_in_competition( $this->competition_id );
		$group_to_form_id = array_reduce(
			$question_groups,
			function ( $res, QuestionGroup $qg ) {
				$res[ $qg->id ] = $qg->form_id;
				return $res;
			},
			array()
		);

		$form_counter           = 0;
		$last_form_id           = null;
		$question_group_counter = 0;
		$last_question_group_id = null;
		$question_counter       = 0;

		$forms_count = count( array_unique( array_values( $group_to_form_id ) ) );

		foreach ( $questions as $q ) {
			if ( $group_to_form_id[ $q->question_group_id ] !== $last_form_id ) {
				$form_counter++;
				$question_group_counter = 0;
				$question_counter       = 0;
			}
			if ( $q->question_group_id !== $last_question_group_id ) {
				$question_group_counter++;
				$question_counter = 0;
			}
			$question_counter++;

			$name = join(
				'.',
				$forms_count > 1
				? array( $form_counter, $question_group_counter, $question_counter )
				: array( $question_group_counter, $question_counter )
			);

			if ( $name !== $q->name ) {
				$q->name = $name;
				$questions_dao->update( $q );
			}

			$last_form_id           = $group_to_form_id[ $q->question_group_id ];
			$last_question_group_id = $q->question_group_id;
		}
	}
}
