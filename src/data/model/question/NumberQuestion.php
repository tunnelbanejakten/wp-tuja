<?php

namespace tuja\data\model\question;


use Exception;
use tuja\data\model\Group;
use tuja\view\FieldNumber;

class NumberQuestion extends AbstractQuestion {

	/**
	 * @tuja-gui-editable
	 */
	public $correct_answer = 0;

	public function __construct( $text, $text_hint, $id, $question_group_id, $sort_order, $score_max, $correct_answer ) {
		parent::__construct( $text, $text_hint, $id, $question_group_id, $sort_order, $score_max );
		$this->correct_answer = $correct_answer;
	}


	/**
	 * Grades an answer and returns the score for the answer.
	 */
	function score( $answer_object ) {
		if ( is_array( $answer_object ) ) {
			$answer_object = $answer_object[0];
		}
		if ( ! is_numeric( $answer_object ) ) {
			return 0;
		}

		return $answer_object == $this->correct_answer ? $this->score_max : 0;
	}

	/**
	 * Returns the HTML used to render this question.
	 */
	function get_html( $field_name, $is_read_only, $answer_object, Group $group = null ) {
		return $this->create_field( $is_read_only )->render( $field_name, $answer_object, $group );
	}

	/**
	 * Gathers data from $_POST about the current question. The response from this function
	 * can be sent to score(...) and can be stored in the database.
	 */
	function get_answer_object( $field_name ) {
		return $this->create_field()->get_posted_answer( $field_name );
	}

	/**
	 * Returns a JSON schema used to validate the question configuration. Also used to generate a form for editing the question.
	 */
	function get_config_schema() {
		throw new Exception( 'get_config_schema() not implemented' );
	}

	private function create_field( $is_read_only = false ) {
		return new FieldNumber(
			$this->text,
			$this->text_hint,
			$is_read_only );
	}

	function get_correct_answer_html() {
		return $this->correct_answer;
	}

	function get_submitted_answer_html( $answer_object, Group $group ) {
		return sprintf( '<var>%f</var>', $answer_object );
	}


}