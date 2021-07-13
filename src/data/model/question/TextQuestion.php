<?php

namespace tuja\data\model\question;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\ValidationException;
use tuja\util\score\AutoScoreResult;
use tuja\view\Field;
use tuja\view\FieldText;
use tuja\view\FieldTextMulti;


class TextQuestion extends AbstractQuestion {

	/**
	 * The lower limit for when an answer is considered correct.
	 * 100 = only an exact match is accepted.
	 * 0   = any answer is accepted.
	 */
	const THRESHOLD = 80;

	// TODO: Properties should not have to be public
	public $score_type = self::GRADING_TYPE_ONE_OF;

	public $correct_answers = [];

	public $incorrect_answers = [];

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
	 * @param int $id
	 * @param int $question_group_id
	 * @param int $sort_order
	 * @param int $score_max
	 * @param string $score_type
	 * @param bool $is_single_answer
	 * @param array $correct_answers
	 * @param array $incorrect_answers
	 */
	public function __construct( $text, $text_hint = null, $id = 0, $question_group_id = 0, $sort_order = 0, $score_max = 0, $score_type = self::GRADING_TYPE_ONE_OF, $is_single_answer = true, $correct_answers = [], $incorrect_answers = [] ) {
		parent::__construct( $text, $text_hint, $id, $question_group_id, $sort_order, $score_max );
		$this->is_single_answer  = $is_single_answer;
		$this->score_type        = $score_type;
		$this->correct_answers   = $correct_answers;
		$this->incorrect_answers = $incorrect_answers;
	}

	public function validate() {
		parent::validate();
		if ( ! empty( $this->score_type ) && ! in_array( $this->score_type, self::SCORING_METHODS ) ) {
			throw new ValidationException( 'score_type', 'Ogiltig poängberäkningsmetod.' );
		}
	}

	public function get_public_properties() {
		return array_merge(
			parent::get_public_properties(),
			array(
				'score_type'       => $this->score_type,
				'is_single_answer' => $this->is_single_answer,
			)
		);
	}

	/**
	 * Grades an answer and returns the score for the answer.
	 */
	function score( $answer_object ): AutoScoreResult {
		if ( ! is_array( $answer_object ) ) {
			throw new Exception( 'Input must be an array. Was: ' . $answer_object );
		}

		$answers           = array_map( 'strtolower', $answer_object );
		$correct_answers   = array_map( 'strtolower', $this->correct_answers );
		$incorrect_answers = array_map( 'strtolower', $this->incorrect_answers );
		$is_ordered        = $this->score_type === self::GRADING_TYPE_ORDERED_PERCENT_OF;

		$correctness_percents = array_map(
			function ( $correctness_percent, $incorrectness_percent ) {
				if ( $incorrectness_percent > $correctness_percent ) {
					// The answer is (mostly) INCORRECT since it is more similar to one of the
					// INCORRECT values than one of the CORRECT ones.

					// We need to "invert" the "correctness value" since the submitted answer is actually incorrect.
					return 100 - $incorrectness_percent;
				} else {
					// The answer is (mostly) CORRECT since it is more similar to one of the
					// CORRECT values than one of the INCORRECT ones.
					return $correctness_percent;
				}
			},
			$this->calculate_correctness( $answers, $correct_answers, $is_ordered ),
			$this->calculate_correctness( $answers, $incorrect_answers, $is_ordered ) );

		$count_correct_values = count( array_filter( $correctness_percents,
			function ( $percent ) {
				return $percent > self::THRESHOLD;
			} ) );

		switch ( $this->score_type ) {
			case self::GRADING_TYPE_ORDERED_PERCENT_OF:
				$confidence = array_sum( array_map( function ( $percent ) {
						return $percent > self::THRESHOLD ? 0.01 * $percent : 1.0 - ( 0.01 * $percent );
					}, $correctness_percents ) ) / count( $answers );

				return new AutoScoreResult( round( $this->score_max / count( $this->correct_answers ) * $count_correct_values ), $confidence );
			case self::GRADING_TYPE_UNORDERED_PERCENT_OF:
				$confidence = 0.01 * array_sum( $correctness_percents ) / count( $correctness_percents );

				return new AutoScoreResult( round( $this->score_max / count( $this->correct_answers ) * $count_correct_values ), $confidence );
			case self::GRADING_TYPE_ONE_OF:
				// TODO: Should multiple answers be allowed?
				// TODO: Should we really use the average confidence here?
				$confidence = array_sum( array_map( function ( $percent ) {
						return $percent > self::THRESHOLD ? 0.01 * $percent : 1.0 - ( 0.01 * $percent );
					}, $correctness_percents ) ) / count( $answers );

				return $count_correct_values > 0
					? new AutoScoreResult( $this->score_max, $confidence )
					: new AutoScoreResult( 0, $confidence );
			case self::GRADING_TYPE_ALL_OF:
				if ( count( $answers ) == count( $correct_answers ) ) {
					$confidence = array_sum( array_map( function ( $percent ) {
							return $percent > self::THRESHOLD ? 0.01 * $percent : 1.0 - ( 0.01 * $percent );
						}, $correctness_percents ) ) / count( $answers );
					$correct = $count_correct_values == count( $correct_answers );

					return new AutoScoreResult(
						$correct ? $this->score_max : 0,
						$confidence );
				} else {
					return new AutoScoreResult( 0, 1.0 );
				}
			default:
				return new AutoScoreResult( 0, 1.0 );
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
	function get_answer_object( string $field_name, $stored_posted_answer ) {
		$field = $this->create_field();

		return $field->get_data( $field_name, $stored_posted_answer );
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
		return join(
			'<br>',
			array_merge(
				$this->correct_answers,
				array_map(
					function ( $value ) {
						return sprintf( '<del>%s</del>', $value );
					},
					$this->incorrect_answers ) ) );
	}
}