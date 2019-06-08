<?php

namespace tuja\data\model\question;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\QuestionDao;
use tuja\view\Field;
use tuja\view\FieldText;
use tuja\view\FieldTextMulti;

class TextQuestion extends AbstractQuestion {

	const VALIDATION_TEXT = 'text';
	const VALIDATION_EMAIL = 'email';
	const VALIDATION_PHONE = 'phone';
	const VALIDATION_PNO = 'pno';

	const ADDITIONAL_PROPERTIES = [
		self::VALIDATION_EMAIL => [
			'type'         => 'email',
			'autocomplete' => 'autocomplete'
		],
		self::VALIDATION_TEXT  => [
			'type' => 'text'
		],
		self::VALIDATION_PHONE => [
			'type'    => 'tel',
			'pattern' => Person::PHONE_PATTERN
		],
		self::VALIDATION_PNO   => [
			'type'        => 'tel',
			'pattern'     => Person::PNO_PATTERN,
			'placeholder' => 'ååmmddnnnn'
		]
	];

	private $score_type;
	private $correct_answers;
	// TODO: $is_single_answer should not have to be public
	public $is_single_answer;
	private $validation;

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
	 * @param string $validation
	 * @param bool $is_single_answer
	 * @param int $question_group_id
	 * @param int $sort_order
	 * @param int $id
	 */
	public function __construct( $text, $text_hint = null, $validation = self::VALIDATION_TEXT, $is_single_answer = true, $question_group_id = 0, $sort_order = 0, $id = 0 ) {
		parent::__construct(
		// TODO: Determine $is_single_answer using "get_config" rather than question type in database.
			$is_single_answer ? QuestionDao::QUESTION_TYPE_TEXT : QuestionDao::QUESTION_TYPE_TEXT_MULTI,
			$question_group_id,
			$text,
			$id,
			$text_hint,
			$sort_order );
		$this->validation       = $validation;
		$this->is_single_answer = $is_single_answer;
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

		$answers_percent_correct = array_map( function ( $answer, $index ) use ( $correct_answers, $is_ordered ) {
			if ( $is_ordered ) {
				// Compare user-supplied answer X to correct answer X:
				$percent = 0;
				similar_text( $answer, $correct_answers[ $index ], $percent );

				return $percent;
			} else {
				// Compare user-supplied answer X to all correct answers (and return the best match):
				$this_answer_percents_correct = array_map( function ( $correct_answer ) use ( $answer ) {
					$percent = 0;
					similar_text( $answer, $correct_answer, $percent );

					return $percent;
				}, $correct_answers );

				return ! empty( $this_answer_percents_correct ) ? max( $this_answer_percents_correct ) : null;
			}
		}, $answers, array_keys( $answers ) );

		$count_correct_values = count( array_filter( $answers_percent_correct,
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

		var_dump( $answer_object );
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

	/**
	 * Returns the configuration data to store in the database for this question.
	 */
	function get_config_object() {
		return [
			'score_type'       => $this->score_type,
			'score_max'        => $this->score_max,
			'correct_answers'  => $this->correct_answers,
			'is_single_answer' => $this->is_single_answer
		];
	}

	/**
	 * Initializes the different properties of the question object based on a string, presumable one returned from get_config_string().
	 */
	function set_config( $config_object ) {
		$this->score_type      = $config_object['score_type'];
		$this->score_max       = $config_object['score_max'];
		$this->correct_answers = $config_object['values'];
//		$this->is_single_answer = $config_object['is_single_answer'];
	}

	private function create_field(): Field {
		if ( $this->is_single_answer ) {
			$field = new FieldText( "question-" . $this->id, $this->text, $this->text_hint, false, self::ADDITIONAL_PROPERTIES[ $this->validation ] );
		} else {
			$field = new FieldTextMulti( "question-" . $this->id, $this->text, $this->text_hint, false );
		}

		return $field;
	}

	function get_correct_answer_html() {
		return join( '<br>', $this->correct_answers );
	}
}