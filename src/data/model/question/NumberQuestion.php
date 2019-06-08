<?php

namespace tuja\data\model\question;


use Exception;
use tuja\data\model\Group;
use tuja\view\FieldNumber;

class NumberQuestion extends AbstractQuestion {

	/**
	 * Grades an answer and returns the score for the answer.
	 */
	function score( $answer_object ) {
		throw new Exception( 'score() not implemented' );
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
		throw new Exception( 'get_correct_answer_html() not implemented' );
	}
}