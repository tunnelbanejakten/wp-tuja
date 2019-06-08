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
	const QUESTION_TYPE_TEXT = 'text';
	const QUESTION_TYPE_NUMBER = 'number';
	const QUESTION_TYPE_PICK_ONE = 'pick_one';
	const QUESTION_TYPE_PICK_MULTI = 'pick_multi';
	const QUESTION_TYPE_IMAGES = 'images';
	const QUESTION_TYPE_TEXT_MULTI = 'text_multi';

	function __construct() {
		parent::__construct();
		$this->table = Database::get_table('form_question');
	}

	private static function class_to_enum( $type ) {
		if ( $type instanceof TextQuestion ) {
			return $type->is_single_answer ? self::QUESTION_TYPE_TEXT : self::QUESTION_TYPE_TEXT_MULTI;
		} elseif ( $type instanceof OptionsQuestion ) {
			return $type->is_single_select ? self::QUESTION_TYPE_PICK_ONE : self::QUESTION_TYPE_PICK_MULTI;
		} elseif ( $type instanceof ImagesQuestion ) {
			return self::QUESTION_TYPE_IMAGES;
		} elseif ( $type instanceof NumberQuestion ) {
			return self::QUESTION_TYPE_IMAGES;
		} else {
			throw new Exception( 'Unsupported type of question: ' . get_class( $type ) );
		}
	}

	function create( AbstractQuestion $question ) {
		$question->validate();
		if ( strlen( self::get_answer_config( $question ) ) > 65000 ) {
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
			self::class_to_enum( $question ),
			self::get_answer_config( $question ),
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
			self::class_to_enum( $question ),
			self::get_answer_config( $question ),
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

	protected static function to_form_question( $result ): AbstractQuestion {
		switch ( $result->type ) {
			case self::QUESTION_TYPE_TEXT_MULTI:
			case self::QUESTION_TYPE_TEXT:
				$q = new TextQuestion(
					$result->text,
					$result->text_hint,
					TextQuestion::VALIDATION_TEXT,
					$result->type == self::QUESTION_TYPE_TEXT,
					$result->question_group_id,
					$result->sort_order,
					$result->id );
				self::set_answer_config( $q, $result->answer );

				return $q;
			case self::QUESTION_TYPE_PICK_ONE:
			case self::QUESTION_TYPE_PICK_MULTI:
				$q = new OptionsQuestion(
					$result->text,
					[],
					$result->text_hint,
					$result->type == self::QUESTION_TYPE_PICK_ONE,
					true,
					$result->id,
					$result->question_group_id,
					$result->sort_order );
				self::set_answer_config( $q, $result->answer );

				return $q;
			case self::QUESTION_TYPE_IMAGES:
				$q = new ImagesQuestion(
					$result->question_group_id,
					$result->text,
					$result->id,
					$result->text_hint,
					$result->sort_order );
				self::set_answer_config( $q, $result->answer );

				return $q;
			case self::QUESTION_TYPE_NUMBER:
				$q = new NumberQuestion(
					$result->question_group_id,
					$result->text,
					$result->id,
					$result->text_hint,
					$result->sort_order );
				self::set_answer_config( $q, $result->answer );

				return $q;
			default:
				throw new Exception( 'Unsupported type of question: ' . $result->type );
		}
	}

	private static function set_answer_config( AbstractQuestion $question, $json_string ) {
		$question->set_config( json_decode( $json_string, true ) );
	}

	private static function get_answer_config( AbstractQuestion $question ) {
		return json_encode( $question->get_config_object() );
	}
}