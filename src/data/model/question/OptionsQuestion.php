<?php

namespace tuja\data\model\question;


use Exception;
use tuja\data\model\Group;
use tuja\util\score\AutoScoreResult;
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

	public $score_type = self::GRADING_TYPE_ONE_OF;

	public $is_single_select; // TODO: Fix inconsistency OptionsQuestion->is_single_select vs FieldChoices->is_multichoice (single vs multi).

	public $possible_answers;

	private $submit_on_change;

	public $correct_answers;

	/**
	 * OptionsQuestion constructor.
	 *
	 * @param $text
	 * @param null $text_hint
	 * @param int $id
	 * @param int $question_group_id
	 * @param int $sort_order
	 * @param int $score_max
	 * @param string $score_type
	 * @param bool $is_single_select
	 * @param array $correct_answers
	 * @param array $possible_answers
	 * @param bool $submit_on_change
	 */
	public function __construct( $text, $text_hint = null, $id = 0, $question_group_id = 0, $sort_order = 0, $score_max = 0, $score_type = self::GRADING_TYPE_ONE_OF, $is_single_select = true, $correct_answers = [], $possible_answers = [], $submit_on_change = true ) {
		parent::__construct( $text, $text_hint, $id, $question_group_id, $sort_order, $score_max );
		$this->is_single_select = $is_single_select;
		$this->possible_answers = $possible_answers;
		$this->submit_on_change = $submit_on_change;
		$this->correct_answers  = $correct_answers;
		$this->score_type       = $score_type;
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
	function score( $answer_object ) : AutoScoreResult {
		if ( ! is_array( $answer_object ) ) {
			throw new Exception( 'Input must be an array' );
		}

		$answers         = array_map( 'strtolower', $answer_object );
		$correct_answers = array_map( 'strtolower', $this->correct_answers );

		$correctness_percents = $this->calculate_correctness( $answers, $correct_answers, false );

		$count_correct_values = count( array_filter( $correctness_percents,
			function ( $percent ) {
				return $percent == 100;
			} ) );

		if ( $this->is_single_select && count( $answer_object ) > 1 ) {
			return new AutoScoreResult(0, 1.0);
		}

		switch ( $this->score_type ) {
			case self::GRADING_TYPE_ONE_OF:
				return $count_correct_values > 0
					? new AutoScoreResult($this->score_max, 1.0)
					: new AutoScoreResult(0, 1.0);
			case self::GRADING_TYPE_ALL_OF:
				return count( $answers ) == count( $correct_answers )
				       && $count_correct_values == count( $correct_answers )
					? new AutoScoreResult($this->score_max, 1.0)
					: new AutoScoreResult(0, 1.0);
			default:
				return new AutoScoreResult(0, 1.0);
		}
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
	function get_answer_object( string $field_name, $stored_posted_answer ) {
		return $this->create_field()->get_data( $field_name, $stored_posted_answer );
	}

	private function create_field(): FieldChoices {
		$field = new FieldChoices(
			$this->text,
			$this->text_hint,
			false,
			$this->possible_answers,
			! $this->is_single_select,
			$this->submit_on_change );

		return $field;
	}

	function get_correct_answer_html() {
		return join( '<br>', $this->correct_answers );
	}
}