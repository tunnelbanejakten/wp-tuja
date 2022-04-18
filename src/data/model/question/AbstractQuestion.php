<?php

namespace tuja\data\model\question;


use tuja\data\model\Group;
use tuja\data\model\ValidationException;
use tuja\util\ReflectionUtils;
use tuja\util\score\AutoScoreResult;

abstract class AbstractQuestion {

	const RESPONSE_MISSING_HTML = '<span class="tuja-admin-noresponse">Inget svar</span>';

	// TODO: Do these properties need to be public?
	public $id                = - 1;
	public $name              = null;
	public $question_group_id = - 1;
	public $text_hint         = 'A subtle hint';
	public $text              = 'Who? What? When?';
	public $sort_order        = 0;
	public $limit_time        = 0;
	public $score_max         = 0;

	/**
	 * AbstractQuestion constructor.
	 *
	 * @param $text
	 * @param $text_hint
	 * @param $id
	 * @param $question_group_id
	 * @param $sort_order
	 * @param $limit_time
	 * @param $score_max
	 */
	public function __construct( $name, $text, $text_hint, $id, $question_group_id, $sort_order, $limit_time, $score_max ) {
		$this->id                = $id;
		$this->name              = $name;
		$this->question_group_id = $question_group_id;
		$this->text_hint         = $text_hint;
		$this->text              = $text;
		$this->sort_order        = $sort_order;
		$this->limit_time        = $limit_time;
		$this->score_max         = $score_max;
	}


	function json_schema() {
		$str = __DIR__ . '/' . substr( get_class( $this ), strlen( __NAMESPACE__ ) + 1 ) . '.schema.json';

		return file_get_contents( $str );
	}

	function get_editable_properties_json() {
		$schema = json_decode( $this->json_schema(), true );

		$editable_properties = array_keys( $schema['properties'] );

		return ReflectionUtils::to_json_string( $this, $editable_properties );
	}

	function set_properties_from_json_string( $json_string ) {
		ReflectionUtils::set_properties_from_json_string(
			$this,
			$json_string,
			$this->json_schema()
		);
	}

	/**
	 * Grades an answer and returns the score for the answer.
	 */
	abstract function score( $answer_object ): AutoScoreResult;

	/**
	 * Returns the HTML used to render this question.
	 */
	abstract function get_html( $field_name, $is_read_only, $answer_object );

	/**
	 * Returns an JSON object used to render this question by external clients.
	 */
	public function get_public_properties() {
		return array(
			'text_hint'  => $this->text_hint,
			'text'       => $this->text,
			'name'       => $this->name,
			'sort_order' => $this->sort_order,
			'limit_time' => $this->limit_time,
			'score_max'  => $this->score_max,
		);
	}

	/**
	 * Gathers data from $_POST about the current question. The response from this function
	 * can be sent to score(...) and can be stored in the database.
	 */
	abstract function get_answer_object( string $field_name, $stored_posted_answer, Group $group );

	abstract function get_correct_answer_html();

	function get_submitted_answer_html( $answer_object, Group $group ) {
		return is_array( $answer_object )
			? join( '<br>', $answer_object ) ?: self::RESPONSE_MISSING_HTML
			: '<em>Ogiltigt svar</em>';
	}

	public function validate() {
		if ( strlen( $this->text ) > 65000 ) {
			throw new ValidationException( 'text', 'Frågan är för lång.' );
		}
		if ( strlen( $this->text_hint ) > 65000 ) {
			throw new ValidationException( 'text_hint', 'Hjälptexten är för lång.' );
		}
	}

	protected function calculate_correctness( array $user_input, array $correct_input, bool $is_ordered ): array {
		$answers_percent_correct = array_map( function ( $answer, $index ) use ( $correct_input, $is_ordered ) {
			if ( $is_ordered ) {
				// Compare user-supplied answer X to correct answer X:
				$percent = 0;
				if ( isset( $correct_input[ $index ] ) ) {
					similar_text( $answer, $correct_input[ $index ], $percent );
				}

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

	public function is_timed() : bool {
		return is_numeric( $this->limit_time ) && $this->limit_time > 0;
	}

	public function get_adjusted_time_limit( Group $group ) {
		$time_limit_multiplier = $group->get_category()->get_rules()->get_time_limit_multiplier();
		$time_limit_adjusted   = round( $this->limit_time * 0.01 * $time_limit_multiplier );
		return $time_limit_adjusted;
	}


}
