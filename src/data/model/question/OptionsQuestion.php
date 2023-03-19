<?php

namespace tuja\data\model\question;

use Exception;
use tuja\data\model\Group;
use tuja\data\model\ValidationException;
use tuja\util\score\AutoScoreResult;
use tuja\view\FieldChoices;

class OptionsQuestion extends AbstractQuestion {

	/**
	 * Full points is awarded if supplied answer matches ONE of the valid answers.
	 * No points is awarded otherwise.
	 */
	const GRADING_TYPE_ONE_OF = 'one_of';

	/**
	 * Full points is awarded if supplied answer matches ALL of the valid answers.
	 * No points is awarded otherwise.
	 */

	const GRADING_TYPE_ALL_OF = 'all_of';

	const SCORING_METHODS = array(
		self::GRADING_TYPE_ALL_OF,
		self::GRADING_TYPE_ONE_OF,
	);

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
	 * @param null   $text_hint
	 * @param int    $id
	 * @param int    $question_group_id
	 * @param int    $sort_order
	 * @param int    $limit_time
	 * @param string $text_preparation
	 * @param int    $score_max
	 * @param string $score_type
	 * @param bool   $is_single_select
	 * @param array  $correct_answers
	 * @param array  $possible_answers
	 * @param bool   $submit_on_change
	 */
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
		$score_type = self::GRADING_TYPE_ONE_OF,
		$is_single_select = true,
		$correct_answers = array(),
		$possible_answers = array(),
		$submit_on_change = true
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

	public function get_public_properties() {
		return array_merge(
			parent::get_public_properties(),
			array(
				'score_type'       => $this->score_type,
				'is_single_select' => $this->is_single_select,
				'possible_answers' => $this->possible_answers,
			)
		);
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

		$count_correct_values = count(
			array_filter(
				$correctness_percents,
				function ( $percent ) {
					return $percent == 100;
				}
			)
		);

		if ( $this->is_single_select && count( $answer_object ) > 1 ) {
			return new AutoScoreResult( 0, 1.0 );
		}

		switch ( $this->score_type ) {
			case self::GRADING_TYPE_ONE_OF:
				return $count_correct_values > 0
					? new AutoScoreResult( $this->score_max, 1.0 )
					: new AutoScoreResult( 0, 1.0 );
			case self::GRADING_TYPE_ALL_OF:
				return count( $answers ) == count( $correct_answers )
					   && $count_correct_values == count( $correct_answers )
					? new AutoScoreResult( $this->score_max, 1.0 )
					: new AutoScoreResult( 0, 1.0 );
			default:
				return new AutoScoreResult( 0, 1.0 );
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
	function get_answer_object( string $field_name, $stored_posted_answer, Group $group ) {
		return $this->create_field()->get_data( $field_name, $stored_posted_answer, $group );
	}

	private function create_field(): FieldChoices {
		$field = new FieldChoices(
			$this->text,
			$this->text_hint,
			false,
			$this->possible_answers,
			! $this->is_single_select,
			$this->submit_on_change
		);

		return $field;
	}

	function get_correct_answer_html() {
		return join( '<br>', $this->correct_answers );
	}

	public function question_type_props_html(): void {
		?>
		<div class="row">
			<div class="form-control repeat">
				<label>Alternativ</label>
				<ol>
					<template>
						<li>
							<input type="text" name="possible_answers[]">
							<button type="button" class="remove">Ta bort</button>
						</li>
					</template>
					<?php foreach($this->possible_answers as $possible_answer): ?>
						<li>
							<input type="text" name="possible_answers[]" value="<?php echo esc_html($possible_answer); ?>">
							<button type="button" class="remove">Ta bort</button>
						</li>
					<?php endforeach; ?>
				</ol>
				<button type="button" class="add">Lägg till</button>
			</div>
		</div>

		<div class="row">
			<div class="form-control repeat">
				<label>Rätta svar</label>
				<ol>
					<template>
						<li>
							<input type="text" name="correct_answers[]">
							<button type="button" class="remove">Ta bort</button>
						</li>
					</template>
					<?php foreach($this->correct_answers as $correct_answer): ?>
						<li>
							<input type="text" name="correct_answers[]" value="<?php echo esc_html($correct_answer); ?>">
							<button type="button" class="remove">Ta bort</button>
						</li>
					<?php endforeach; ?>
				</ol>
				<button type="button" class="add">Lägg till</button>
			</div>
		</div>

		<div class="row">
			<div class="form-control checkbox">
				<input type="checkbox" name="is_single_select" id="is_single_select"<?php checked($this->is_single_select); ?>>
				<label for="is_single_select">Bara ett svar</label>
			</div>
		</div>

		<div class="row">
			<label>Rättningsmekanism</label>
		</div>

		<div class="row">
			<div class="form-control radio">
				<input type="radio" name="score_type" id="score_type__one_of" value="one_of"<?php checked($this->score_type, 'one_of'); ?>>
				<label for="score_type__one_of">Minst ett rätt svar</label>
			</div>
		</div>
		<div class="row">
			<div class="form-control radio">
				<input type="radio" name="score_type" id="score_type__all_of" value="all_of"<?php checked($this->score_type, 'all_of'); ?>>
				<label for="score_type__all_of">Alla rätt</label>
			</div>
		</div>
		<?php
	}
}
