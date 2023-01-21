<?php

namespace tuja\data\model\question;

use tuja\data\model\Group;
use tuja\util\score\AutoScoreResult;
use tuja\view\FieldNumber;

class NumberQuestion extends AbstractQuestion {

	public $correct_answer = 0;

	public function __construct(
		$name = null,
		$text = '',
		$text_hint = null,
		$id = 0,
		$question_group_id = 0,
		$sort_order = 0,
		$limit_time = -1,
		$text_preparation = null,
		$score_max = 0,
		$correct_answer = null
	) {
		parent::__construct(
			$name,
			$text,
			$text_hint,
			$id,
			$question_group_id,
			$sort_order,
			$limit_time,
			$text_preparation,
			$score_max
		);
		$this->correct_answer = $correct_answer;
	}

	private static function get_adjusted_answer_object( $answer_object ) {
		if ( is_array( $answer_object ) ) {
			return $answer_object[0];
		}
		return $answer_object;
	}

	/**
	 * Grades an answer and returns the score for the answer.
	 */
	function score( $original_answer_object ): AutoScoreResult {
		$answer_object = self::get_adjusted_answer_object( $original_answer_object );
		if ( ! is_numeric( $answer_object ) ) {
			return new AutoScoreResult( 0, 1.0 );
		}

		$diff_max = abs( $this->correct_answer * 0.1 );

		$diff = abs( $answer_object - $this->correct_answer );

		if ( $diff == 0 ) {
			// Exactly correct
			return new AutoScoreResult( $this->score_max, 1.0 );
		} elseif ( $diff < $diff_max ) {
			// Almost correct
			$confidence = 1.0 - ( ( $diff_max - $diff ) / $diff_max );

			$score = $diff == 0 ? $this->score_max : 0;

			return new AutoScoreResult( $score, $confidence );
		} else {
			// Definitely wrong
			return new AutoScoreResult( 0, 1.0 );
		}

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
	function get_answer_object( string $field_name, $stored_posted_answer, Group $group ) {
		return $this->create_field()->get_data( $field_name, $stored_posted_answer, $group );
	}

	private function create_field( $is_read_only = false ) {
		return new FieldNumber(
			$this->text,
			$this->text_hint,
			$is_read_only
		);
	}

	function get_correct_answer_html() {
		return number_format_i18n( $this->correct_answer );
	}

	function get_submitted_answer_html( $original_answer_object, Group $group ) {
		$answer_object = self::get_adjusted_answer_object( $original_answer_object );
		if ( ! isset( $answer_object ) || $answer_object == '' ) {
			return AbstractQuestion::RESPONSE_MISSING_HTML;
		}

		if ( ! is_numeric( $answer_object ) ) {
			return '<em>Har inte svarat med ett nummer.</em>';
		}

		return number_format_i18n( $answer_object );
	}
}
