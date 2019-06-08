<?php

namespace tuja\data\model\question;


use Exception;
use tuja\data\model\Group;
use tuja\view\FieldImages;

class ImagesQuestion extends AbstractQuestion {

	public function __construct( $question_group_id, $text, $id, $text_hint, $sort_order ) {
		parent::__construct( 'images', $question_group_id, $text, $id, $text_hint, $sort_order );
	}


	/**
	 * Grades an answer and returns the score for the answer.
	 */
	function score( $answer_object ) {
		return 0;
//		throw new Exception( 'score() not implemented' );
	}

	/**
	 * Returns the HTML used to render this question.
	 */
	function get_html( $field_name, $is_read_only, $answer_object, Group $group = null ) {
		return $this->create_field($is_read_only)->render( $field_name, $answer_object, $group );
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

	/**
	 * Returns the configuration data to store in the database for this question.
	 */
	function get_config_object() {
		return null;
//		throw new Exception( 'get_config_string() not implemented' );
	}

	/**
	 * Initializes the different properties of the question object based on a string, presumable one returned from get_config_string().
	 */
	function set_config( $config_object ) {
//		throw new Exception( 'set_config() not implemented' );
	}

	private function create_field($is_read_only = false): FieldImages {
		$field = new FieldImages(
			"question-" . $this->id,
			$this->text,
			$this->text_hint,
			$is_read_only);

		return $field;
	}

	function get_correct_answer_html() {
		return null;
	}
}