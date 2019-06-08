<?php

namespace tuja\data\model\question;


use Exception;
use tuja\data\model\Group;
use tuja\view\FieldChoices;

class OptionsQuestion extends AbstractQuestion {

	// TODO: $is_single_select should not have to be public
	public $is_single_select;
	private $possible_answers;
	private $submit_on_change;
	private $correct_answers;

	/**
	 * OptionsQuestion constructor.
	 *
	 * @param $text
	 * @param array $possible_answers
	 * @param null $text_hint
	 * @param bool $is_single_select
	 * @param bool $submit_on_change
	 * @param int $id
	 * @param int $question_group_id
	 * @param int $sort_order
	 */
	public function __construct( $text, $possible_answers = [], $text_hint = null, $is_single_select = true, $submit_on_change = true, $id = 0, $question_group_id = 0, $sort_order = 0 ) {
		parent::__construct( $is_single_select ? 'pick_one' : 'pick_multi', $question_group_id, $text, $id, $text_hint, $sort_order );
		$this->is_single_select = $is_single_select;
		$this->possible_answers = $possible_answers;
		$this->submit_on_change = $submit_on_change;
	}


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
		return $this->create_field()->render( $field_name, $answer_object, $group );
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
		return [
			'is_single_select' => $this->is_single_select,
			'possible_answers' => $this->possible_answers
		];
	}

	/**
	 * Initializes the different properties of the question object based on a string, presumable one returned from get_config_string().
	 */
	function set_config( $config_object ) {
		$this->is_single_select = $config_object['is_single_select'];
		$this->possible_answers = $config_object['options'];
		$this->correct_answers = $config_object['values'];
	}

	/**
	 * @param $answer_object
	 *
	 * @return FieldChoices
	 */
	private function create_field(): FieldChoices {
		$field = new FieldChoices(
			"question-" . $this->id,
			$this->text,
			$this->possible_answers,
			! $this->is_single_select,
			$this->text_hint,
			false,
			$this->submit_on_change);

		return $field;
	}

	function get_correct_answer_html() {
		return join( '<br>', $this->correct_answers );
	}
}