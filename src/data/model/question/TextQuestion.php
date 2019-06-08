<?php

namespace tuja\data\model\question;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\ValidationException;
use tuja\view\Field;
use tuja\view\FieldText;
use tuja\view\FieldTextMulti;

class TextQuestion extends AbstractQuestion {

	// TODO: Properties should not have to be public
	/**
	 * @tuja-gui-editable
	 */
	public $score_type = self::GRADING_TYPE_ONE_OF;

	/**
	 * @tuja-gui-editable
	 */
	public $correct_answers = [];

	/**
	 * @tuja-gui-editable
	 */
	public $is_single_answer = true;

	/**
	 * Full points is awarded if supplied answer matches ONE of the valid answers.
	 * No points is awarded otherwise.
	 */
	const GRADING_TYPE_ONE_OF = "one_of";

	/**
	 * Points is awarded based on how many of the valid answers the user supplied.
	 * The user is allowed to type answers in any order. The only thing that matters
	 * is whether or not a valid answer has been supplied or not.
	 *
	 * Examples:
	 * - Full points is award if user specifies ['alice', 'bob'] and valid answers are ['alice', 'bob']
	 * - Full points is award if user specifies ['bob', 'alice'] and valid answers are ['alice', 'bob'] (wrong order but that's okay).
	 * - Half points is award if user specifies ['alice', ''] and valid answers are ['alice', 'bob'].
	 */
	const GRADING_TYPE_UNORDERED_PERCENT_OF = "unordered_percent_of";

	/**
	 * Points is awarded based on how many of the valid answers the user supplied.
	 * The order of the user's answers must match the order of the valid answers.
	 *
	 * Examples:
	 * - Full points is award if user specifies ['alice', 'bob'] and valid answers are ['alice', 'bob']
	 * - No points is award if user specifies ['bob', 'alice'] and valid answers are ['alice', 'bob'] (wrong order).
	 * - Half points is award if user specifies ['alice', ''] and valid answers are ['alice', 'bob'].
	 */
	const GRADING_TYPE_ORDERED_PERCENT_OF = "ordered_percent_of";

	/**
	 * Full points is awarded if supplied answer matches ALL of the valid answers.
	 * No points is awarded otherwise.
	 */

	const GRADING_TYPE_ALL_OF = "all_of";

	const SCORING_METHODS = [
		self::GRADING_TYPE_ALL_OF,
		self::GRADING_TYPE_UNORDERED_PERCENT_OF,
		self::GRADING_TYPE_ORDERED_PERCENT_OF,
		self::GRADING_TYPE_ONE_OF
	];

	/**
	 * TextQuestion constructor.
	 *
	 * @param $text
	 * @param $text_hint
	 * @param bool $is_single_answer
	 * @param int $question_group_id
	 * @param int $sort_order
	 * @param int $id
	 */
	public function __construct( $text, $text_hint = null, $is_single_answer = true, $question_group_id = 0, $sort_order = 0, $id = 0, $score_max = 0, $score_type = self::GRADING_TYPE_ONE_OF, $correct_answers = [] ) {
		parent::__construct( $text, $text_hint, $id, $question_group_id, $sort_order, $score_max );
		$this->is_single_answer = $is_single_answer;
		$this->score_type       = $score_type;
		$this->correct_answers  = $correct_answers;
	}

	public function validate() {
		parent::validate();
		if ( ! empty( $this->score_type ) && ! in_array( $this->score_type, self::SCORING_METHODS ) ) {
			throw new ValidationException( 'score_type', 'Ogiltig poängberäkningsmetod.' );
		}
	}


	/**
	 * Grades an answer and returns the score for the answer.
	 */
	function score( $answer_object ) {
		if ( ! is_array( $answer_object ) ) {
			throw new Exception( 'Input must be an array' );
		}

		$answers         = array_map( 'strtolower', $answer_object );
		$correct_answers = array_map( 'strtolower', $this->correct_answers );
		$is_ordered      = $this->score_type === self::GRADING_TYPE_ORDERED_PERCENT_OF;

		$correctness_percents = $this->calculate_correctness( $answers, $correct_answers, $is_ordered );

		$count_correct_values = count( array_filter( $correctness_percents,
			function ( $percent ) {
				return $percent > 80;
			} ) );

		switch ( $this->score_type ) {
			case self::GRADING_TYPE_ORDERED_PERCENT_OF:
			case self::GRADING_TYPE_UNORDERED_PERCENT_OF:
				return round( $this->score_max / count( $this->correct_answers ) * $count_correct_values );
			case self::GRADING_TYPE_ONE_OF:
				return $count_correct_values > 0
					? $this->score_max
					: 0;
			case self::GRADING_TYPE_ALL_OF:
				return count( $answers ) == count( $correct_answers )
				       && $count_correct_values == count( $correct_answers )
					? $this->score_max
					: 0;
			default:
				return 0;
		}
	}

	/**
	 * Returns the HTML used to render this question.
	 */
	function get_html( $field_name, $is_read_only, $answer_object, Group $group = null ) {
		$field = $this->create_field();

		return $field->render( $field_name, $answer_object, $group );
	}

	/**
	 * Gathers data from $_POST about the current question. The response from this function
	 * can be sent to score(...) and can be stored in the database.
	 */
	function get_answer_object( $field_name ) {
		$field = $this->create_field();

		return $field->get_posted_answer( $field_name );
	}

	/**
	 * Returns a JSON schema used to validate the question configuration. Also used to generate a form for editing the question.
	 */
	function get_config_schema() {
		throw new Exception( 'get_config_schema() not implemented' );
	}

	private function create_field(): Field {
		if ( $this->is_single_answer ) {
			$field = new FieldText( $this->text, $this->text_hint, false );
		} else {
			$field = new FieldTextMulti( $this->text, $this->text_hint, false );
		}

		return $field;
	}

	function get_correct_answer_html() {
		return join( '<br>', $this->correct_answers );
	}
}