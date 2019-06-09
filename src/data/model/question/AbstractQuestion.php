<?php

namespace tuja\data\model\question;


use tuja\data\model\Group;
use tuja\data\model\ValidationException;
use tuja\util\ReflectionUtils;

abstract class AbstractQuestion {

	// TODO: Do these properties need to be public?
	public $id = - 1;
	public $question_group_id = - 1;

	/**
	 * @tuja-gui-editable
	 */
	public $text_hint = 'A subtle hint';

	/**
	 * @tuja-gui-editable
	 */
	public $text = 'Who? What? When?';

	/**
	 * @tuja-gui-editable
	 */
	public $sort_order = 0;

	/**
	 * @tuja-gui-editable
	 */
	public $score_max = 0;

	/**
	 * AbstractQuestion constructor.
	 *
	 * @param $text
	 * @param $text_hint
	 * @param $id
	 * @param $question_group_id
	 * @param $sort_order
	 * @param $score_max
	 */
	public function __construct( $text, $text_hint, $id, $question_group_id, $sort_order, $score_max ) {
		$this->id                = $id;
		$this->question_group_id = $question_group_id;
		$this->text_hint         = $text_hint;
		$this->text              = $text;
		$this->sort_order        = $sort_order;
		$this->score_max         = $score_max;
	}


	/**
	 * Grades an answer and returns the score for the answer.
	 */
	abstract function score( $answer_object );

	/**
	 * Returns the HTML used to render this question.
	 */
	abstract function get_html( $field_name, $is_read_only, $answer_object );

	/**
	 * Gathers data from $_POST about the current question. The response from this function
	 * can be sent to score(...) and can be stored in the database.
	 */
	abstract function get_answer_object( $field_name );

	abstract function get_correct_answer_html();

	function get_submitted_answer_html( $answer_object, Group $group ) {
		return is_array( $answer_object ) ? join( '<br>', $answer_object ) : '<em>Ogiltigt svar</em>';
	}

	public function validate() {
		if ( strlen( $this->text ) > 65000 ) {
			throw new ValidationException( 'text', 'Frågan är för lång.' );
		}
		if ( strlen( $this->text_hint ) > 65000 ) {
			throw new ValidationException( 'text_hint', 'Hjälptexten är för lång.' );
		}
	}

	public function get_editable_fields() {
		return ReflectionUtils::get_editable_properties( $this );
	}

	protected function calculate_correctness( array $user_input, array $correct_input, bool $is_ordered ): array {
		$answers_percent_correct = array_map( function ( $answer, $index ) use ( $correct_input, $is_ordered ) {
			if ( $is_ordered ) {
				// Compare user-supplied answer X to correct answer X:
				$percent = 0;
				similar_text( $answer, $correct_input[ $index ], $percent );

				return $percent;
			} else {
				// Compare user-supplied answer X to all correct answers (and return the best match):
				$this_answer_percents_correct = array_map( function ( $correct_answer ) use ( $answer ) {
					$percent = 0;
					similar_text( $answer, $correct_answer, $percent );

					return $percent;
				}, $correct_input );

				return ! empty( $this_answer_percents_correct ) ? max( $this_answer_percents_correct ) : null;
			}
		}, $user_input, array_keys( $user_input ) );

		return $answers_percent_correct;
	}
}