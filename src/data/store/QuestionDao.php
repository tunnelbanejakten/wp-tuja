<?php

namespace tuja\data\store;

use Exception;
use ReflectionClass;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\question\ImagesQuestion;
use tuja\data\model\question\NumberQuestion;
use tuja\data\model\question\OptionsQuestion;
use tuja\data\model\question\TextQuestion;
use tuja\data\model\Response;
use tuja\data\model\ValidationException;
use tuja\util\Database;

class QuestionDao extends AbstractDao {
	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'form_question' );
	}

	private static function get_answer_config( AbstractQuestion $question ) {
		if ( $question instanceof TextQuestion ) {
			return array(
				'score_max'      => $question->score_max,
				'score_type'     => $question->score_type,
				'values'         => $question->correct_answers,
				'invalid_values' => $question->incorrect_answers,
			);
		} elseif ( $question instanceof OptionsQuestion ) {
			return array(
				'score_max'  => $question->score_max,
				'score_type' => $question->score_type,
				'options'    => $question->possible_answers,
				'values'     => $question->correct_answers,
			);
		} elseif ( $question instanceof ImagesQuestion ) {
			return array(
				'score_max'       => $question->score_max,
				'max_files_count' => $question->max_files_count,
			);
		} elseif ( $question instanceof NumberQuestion ) {
			return array(
				'score_max' => $question->score_max,
				'value'     => $question->correct_answer,
			);
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
                name,
                answer,
                text,
                sort_order,
                limit_time,
                text_preparation,
                text_hint
            ) VALUES (
                %s,
                %d,
                %s,
                %s,
                %s,
                %s,
                %d,
                %d,
                %s,
                %s 
			)';
		$query          = $this->wpdb->prepare(
			$query_template,
			$this->id->random_string(),
			$question->question_group_id,
			self::get_db_type( $question ),
			$question->name,
			$answer_config,
			$question->text,
			$question->sort_order,
			$question->limit_time > 0 ? $question->limit_time : null,
			$question->text_preparation,
			$question->text_hint
		);

		$affected_rows = $this->wpdb->query( $query );

		$success = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
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
                name = %s,
                answer = %s,
                text = %s,
                sort_order = %d,
                limit_time = %d,
                text_preparation = %s,
                text_hint = %s
                WHERE 
                id = %d';

		return $this->wpdb->query(
			$this->wpdb->prepare(
				$query_template,
				self::get_db_type( $question ),
				$question->name,
				$answer_config,
				$question->text,
				$question->sort_order,
				$question->limit_time > 0 ? $question->limit_time : null,
				$question->text_preparation,
				$question->text_hint,
				$question->id
			)
		);
	}

	public function get( $question_id ) {
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
			$question_id
		);

		return current( $objects );
	}

	public function get_by_key( string $question_key ) {
		$objects = $this->get_objects(
			function ( $row ) {
				return self::to_form_question( $row );
			},
			'
				SELECT q.* 
                FROM ' . $this->table . ' AS q
                WHERE q.random_id = %s
			',
			$question_key
		);

		return current( $objects );
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
                ORDER BY 
					grp.form_id,
					grp.sort_order, 
					grp.id, 
					q.sort_order, 
					q.id',
			$form_id
		);
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
			$group_id
		);
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
				ORDER BY 
					grp.form_id,
					grp.sort_order, 
					grp.id, 
					q.sort_order, 
					q.id',
			$competition_id
		);
	}
}
