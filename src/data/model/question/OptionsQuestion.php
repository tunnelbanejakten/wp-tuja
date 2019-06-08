<?php

namespace tuja\data\model\question;


use Exception;
use tuja\data\model\Group;
use tuja\view\FieldChoices;

class OptionsQuestion extends AbstractQuestion {

	/**
	 * Full points is awarded if supplied answer matches ONE of the valid answers.
	 * No points is awarded otherwise.
	 */
	const GRADING_TYPE_ONE_OF = "one_of";

	/**
	 * Full points is awarded if supplied answer matches ALL of the valid answers.
	 * No points is awarded otherwise.
	 */

	const GRADING_TYPE_ALL_OF = "all_of";

	const SCORING_METHODS = [
		self::GRADING_TYPE_ALL_OF,
		self::GRADING_TYPE_ONE_OF
	];

	// TODO: Properties should not have to be public

	/**
	 * @tuja-gui-editable
	 */
	public $score_type = self::GRADING_TYPE_ONE_OF;

	/**
	 * @tuja-gui-editable
	 */
	public $is_single_select;

	/**
	 * @tuja-gui-editable
	 */
	public $possible_answers;

	private $submit_on_change;

	/**
	 * @tuja-gui-editable
	 */
	public $correct_answers;

	/**
	 * OptionsQuestion constructor.
	 *
	 * @param $text
	 * @param null $text_hint
	 * @param array $possible_answers
	 * @param bool $is_single_select
	 * @param bool $submit_on_change
	 * @param int $id
	 * @param int $question_group_id
	 * @param int $sort_order
	 */
	public function __construct( $text, $text_hint = null, $possible_answers = [], $is_single_select = true, $submit_on_change = true, $id = 0, $question_group_id = 0, $sort_order = 0, $correct_answers = [], $score_max = 0, $score_type = self::GRADING_TYPE_ONE_OF ) {
		parent::__construct( $text, $text_hint, $id, $question_group_id, $sort_order, $score_max );
		$this->is_single_select = $is_single_select;
		$this->possible_answers = $possible_answers;
		$this->submit_on_change = $submit_on_change;
		$this->correct_answers  = $correct_answers;
		$this->score_type       = $score_type;
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
	 * @param $answer_object
	 *
	 * @return FieldChoices
	 */
	private function create_field(): FieldChoices {
		$field = new FieldChoices(
			$this->text,
			$this->possible_answers,
			! $this->is_single_select,
			$this->text_hint,
			false,
			$this->submit_on_change );

		return $field;
	}

	function get_correct_answer_html() {
		return join( '<br>', $this->correct_answers );
	}
}