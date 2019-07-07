<?php

namespace tuja\data\store;

use Exception;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\question\ImagesQuestion;
use tuja\data\model\question\NumberQuestion;
use tuja\data\model\question\OptionsQuestion;
use tuja\data\model\question\TextQuestion;
use tuja\data\model\ValidationException;
use tuja\util\Database;

class QuestionDao extends AbstractDao
{
	function __construct() {
		parent::__construct();
		$this->table = Database::get_table('form_question');
	}

	private static function get_answer_config( AbstractQuestion $question ) {
		if ( $question instanceof TextQuestion ) {
			return [
				'score_max'  => $question->score_max,
				'score_type' => $question->score_type,
				'values'     => $question->correct_answers
			];
		} elseif ( $question instanceof OptionsQuestion ) {
			return [
				'score_max' => $question->score_max,
				'options'   => $question->possible_answers,
				'values'    => $question->correct_answers
			];
		} elseif ( $question instanceof ImagesQuestion ) {
			return [
				'score_max' => $question->score_max
			];
		} elseif ( $question instanceof NumberQuestion ) {
			return [
				'score_max' => $question->score_max,
				'value' => $question->correct_answer
			];
		} else {
			throw new Exception( 'Unsupported type of question: ' . get_class( $question ) );
		}

	}

	private static function get_db_type( AbstractQuestion $question ) {
		if ( $question instanceof TextQuestion ) {
			return $question->is_single_answer ? self::QUESTION_TYPE_TEXT : self::QUESTION_TYPE_TEXT_MULTI;
		} elseif ( $question instanceof OptionsQuestion ) {
			return $question->is_single_select ? self::QUESTION_TYPE_PICK_ONE : self::QUESTION_TYPE_PICK_MULTI;
		} elseif ( $question instanceof ImagesQuestion ) {
			return self::QUESTION_TYPE_IMAGES;
		} elseif ( $question instanceof NumberQuestion ) {
			return self::QUESTION_TYPE_NUMBER;
		} else {
			throw new Exception( 'Unsupported type of question: ' . get_class( $question ) );
		}
	}

	function create( AbstractQuestion $question ) {
		$question->validate();
		$answer_config = json_encode( self::get_answer_config( $question ) );
		if ( strlen( $answer_config ) > 65000 ) {
			throw new ValidationException( 'answer', 'För mycket konfiguration för frågan.' );
		}

		$query_template = '
            INSERT INTO ' . $this->table . ' (
                random_id,
                question_group_id,
                type,
                answer,
                text,
                sort_order,
                text_hint
            ) VALUES (
                %s,
                %d,
                %s,
                %s,
                %s,
                %d,
                %s 
			)';
		$query          = $this->wpdb->prepare( $query_template,
			$this->id->random_string(),
			$question->question_group_id,
			self::get_db_type( $question ),
			$answer_config,
			$question->text,
			$question->sort_order,
			$question->text_hint
		);

		return $this->wpdb->query( $query );
	}

	function delete( $id ) {
		$query_template = 'DELETE FROM ' . $this->table . ' WHERE id = %d';

		return $this->wpdb->query( $this->wpdb->prepare( $query_template, $id ) );
	}

	function update( AbstractQuestion $question ) {
		$question->validate();
		$answer_config = json_encode( self::get_answer_config( $question ) );
		if ( strlen( $answer_config ) > 65000 ) {
			throw new ValidationException( 'answer', 'För mycket konfiguration för frågan.' );
		}

		$query_template = '
            UPDATE ' . $this->table . ' SET
                type = %s,
                answer = %s,
                text = %s,
                sort_order = %d,
                text_hint = %s
                WHERE 
                id = %d';

		return $this->wpdb->query( $this->wpdb->prepare( $query_template,
			self::get_db_type( $question ),
			$answer_config,
			$question->text,
			$question->sort_order,
			$question->text_hint,
			$question->id ) );
	}

	public function get($question_id) {
		$objects = $this->get_objects(
			function ( $row ) {
				return self::to_form_question( $row );
			},
			'
				SELECT q.* 
                FROM ' . $this->table . ' AS q
                WHERE q.id = %d
				ORDER BY q.sort_order, q.id
			',
			$question_id );

		return reset($objects);
	}

	function get_all_in_form( $form_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_form_question( $row );
			},
			'
                SELECT q.* 
                FROM ' . $this->table . ' AS q 
                    INNER JOIN ' . Database::get_table( 'form_question_group' ) . ' AS grp ON q.question_group_id = grp.id
                WHERE grp.form_id = %d
                ORDER BY grp.sort_order, q.sort_order, q.id',
			$form_id );
	}

	function get_all_in_group( $group_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_form_question( $row );
			},
			'
                SELECT q.* 
                FROM ' . $this->table . ' AS q 
				WHERE q.question_group_id = %d
                ORDER BY q.sort_order, q.id',
			$group_id );
	}

	function get_all_in_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_form_question( $row );
			},
			'
                SELECT q.* 
                FROM ' . $this->table . ' AS q 
                    INNER JOIN ' . Database::get_table( 'form_question_group' ) . ' AS grp ON q.question_group_id = grp.id
                    INNER JOIN ' . Database::get_table( 'form' ) . ' AS f ON grp.form_id = f.id
                WHERE f.competition_id = %d
                ORDER BY grp.sort_order, q.sort_order, q.id',
			$competition_id );
	}
}