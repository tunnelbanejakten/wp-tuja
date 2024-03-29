<?php

namespace tuja\data\model\question;

use Exception;
use tuja\data\model\Group;
use tuja\data\model\ValidationException;
use tuja\util\score\AutoScoreResult;
use tuja\view\Field;
use tuja\view\FieldText;
use tuja\view\FieldTextMulti;
use tuja\admin\AdminUtils;


class TextQuestion extends AbstractQuestion {

	/**
	 * The lower limit for when an answer is considered correct.
	 * 100 = only an exact match is accepted.
	 * 0   = any answer is accepted.
	 */
	const THRESHOLD = 80;

	// TODO: Properties should not have to be public
	public $score_type = self::GRADING_TYPE_ONE_OF;

	public $correct_answers = array();

	public $incorrect_answers = array();

	public $is_single_answer = true;

	/**
	 * Full points is awarded if supplied answer matches ONE of the valid answers.
	 * No points is awarded otherwise.
	 */
	const GRADING_TYPE_ONE_OF = 'one_of';

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
	const GRADING_TYPE_UNORDERED_PERCENT_OF = 'unordered_percent_of';

	/**
	 * Points is awarded based on how many of the valid answers the user supplied.
	 * The order of the user's answers must match the order of the valid answers.
	 *
	 * Examples:
	 * - Full points is award if user specifies ['alice', 'bob'] and valid answers are ['alice', 'bob']
	 * - No points is award if user specifies ['bob', 'alice'] and valid answers are ['alice', 'bob'] (wrong order).
	 * - Half points is award if user specifies ['alice', ''] and valid answers are ['alice', 'bob'].
	 */
	const GRADING_TYPE_ORDERED_PERCENT_OF = 'ordered_percent_of';

	/**
	 * Full points is awarded if supplied answer matches ALL of the valid answers.
	 * No points is awarded otherwise.
	 */

	const GRADING_TYPE_ALL_OF = 'all_of';

	const SCORING_METHODS = array(
		self::GRADING_TYPE_ALL_OF,
		self::GRADING_TYPE_UNORDERED_PERCENT_OF,
		self::GRADING_TYPE_ORDERED_PERCENT_OF,
		self::GRADING_TYPE_ONE_OF,
	);

	/**
	 * TextQuestion constructor.
	 *
	 * @param $text
	 * @param $text_hint
	 * @param int       $id
	 * @param int       $question_group_id
	 * @param int       $sort_order
	 * @param int       $limit_time
	 * @param string    $text_preparation
	 * @param int       $score_max
	 * @param string    $score_type
	 * @param bool      $is_single_answer
	 * @param array     $correct_answers
	 * @param array     $incorrect_answers
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
		$score_max = -1,
		$score_type = self::GRADING_TYPE_ONE_OF,
		$is_single_answer = true,
		$correct_answers = array(),
		$incorrect_answers = array()
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
			$this->calculate_correctness( $answers, $incorrect_answers, $is_ordered )
		);

		$count_correct_values = count(
			array_filter(
				$correctness_percents,
				function ( $percent ) {
					return $percent > self::THRESHOLD;
				}
			)
		);

		switch ( $this->score_type ) {
			case self::GRADING_TYPE_ORDERED_PERCENT_OF:
				$confidence = array_sum(
					array_map(
						function ( $percent ) {
							return $percent > self::THRESHOLD ? 0.01 * $percent : 1.0 - ( 0.01 * $percent );
						},
						$correctness_percents
					)
				) / count( $answers );

				return new AutoScoreResult( round( $this->score_max / count( $this->correct_answers ) * $count_correct_values ), $confidence );
			case self::GRADING_TYPE_UNORDERED_PERCENT_OF:
				$confidence = 0.01 * array_sum( $correctness_percents ) / count( $correctness_percents );

				return new AutoScoreResult( round( $this->score_max / count( $this->correct_answers ) * $count_correct_values ), $confidence );
			case self::GRADING_TYPE_ONE_OF:
				// TODO: Should multiple answers be allowed?
				// TODO: Should we really use the average confidence here?
				$confidence = array_sum(
					array_map(
						function ( $percent ) {
							return $percent > self::THRESHOLD ? 0.01 * $percent : 1.0 - ( 0.01 * $percent );
						},
						$correctness_percents
					)
				) / count( $answers );

				return $count_correct_values > 0
					? new AutoScoreResult( $this->score_max, $confidence )
					: new AutoScoreResult( 0, $confidence );
			case self::GRADING_TYPE_ALL_OF:
				if ( count( $answers ) == count( $correct_answers ) ) {
					$confidence = array_sum(
						array_map(
							function ( $percent ) {
								return $percent > self::THRESHOLD ? 0.01 * $percent : 1.0 - ( 0.01 * $percent );
							},
							$correctness_percents
						)
					) / count( $answers );
					$correct    = $count_correct_values == count( $correct_answers );

					return new AutoScoreResult(
						$correct ? $this->score_max : 0,
						$confidence
					);
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
	function get_answer_object( string $field_name, $stored_posted_answer, Group $group ) {
		$field = $this->create_field();

		return $field->get_data( $field_name, $stored_posted_answer, $group );
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
					$this->incorrect_answers
				)
			)
		);
	}

	public function question_type_props_html(): void {
		?>
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
			<div class="form-control repeat">
				<label>Felaktiga svar <?php AdminUtils::printTooltip( 'Felaktiga svar är svar som liknar korrekta svar men som alltså ska räknas som felaktiga. Används i fall då auto-rättningen är för snäll, dvs. ger poäng för något som liknar rätt svar men som egentligen är fel.' ); ?></label>
				<ol>
					<template>
						<li>
							<input type="text" name="incorrect_answers[]">
							<button type="button" class="remove">Ta bort</button>
						</li>
					</template>
					<?php foreach($this->incorrect_answers as $incorrect_answer): ?>
						<li>
							<input type="text" name="incorrect_answers[]" value="<?php echo esc_html($incorrect_answer); ?>">
							<button type="button" class="remove">Ta bort</button>
						</li>
					<?php endforeach; ?>
				</ol>
				<button type="button" class="add">Lägg till</button>
			</div>
		</div>

		<div class="row">
			<div class="form-control checkbox">
				<input type="checkbox" name="is_single_answer" id="is_single_answer"<?php checked($this->is_single_answer); ?>>
				<label for="is_single_answer">Bara ett svar <?php AdminUtils::printTooltip( 'De tävlande får bara lämna ett svar på frågan. Annars tillåts att man svarar flera saker.' ); ?></label>
			</div>
		</div>

		<div class="row">
			<label>Rättningsmekanism</label>
		</div>

		<div class="row">
			<div class="form-control radio">
				<input type="radio" name="score_type" required id="score_type__one_of" value="one_of"<?php checked($this->score_type, 'one_of'); ?>>
				<label for="score_type__one_of">Minst ett rätt svar <?php AdminUtils::printTooltip( 'Maximal poäng om (minst) ett av de rätta svaren anges. Annars noll poäng.' ); ?></label>
			</div>
		</div>
		<div class="row">
			<div class="form-control radio">
				<input type="radio" name="score_type" required id="score_type__unordered_percent_of" value="unordered_percent_of"<?php checked($this->score_type, 'unordered_percent_of'); ?>>
				<label for="score_type__unordered_percent_of">Andel rätta svar <?php AdminUtils::printTooltip( 'Poäng baserat på hur många av de rätta svaren som angetts. Exempel: Om en fråga har tre rätta svar och två av dessa lämnats så får man 2/3 av maxpoäng.' ); ?></label>
			</div>
		</div>
		<div class="row">
			<div class="form-control radio">
				<input type="radio" name="score_type" required id="score_type__ordered_percent_of" value="ordered_percent_of"<?php checked($this->score_type, 'ordered_percent_of'); ?>>
				<label for="score_type__ordered_percent_of">Andel rätta svar i korrekt ordning <?php AdminUtils::printTooltip( 'Poäng baserat på hur många av de rätta svaren som angetts i samma ordning som här ovan. Exempel: Om svaren är Adam, Bertil, Cecilia, Doris men användaren svarat Adam, Cecilia, Bertil, Doris (dvs. bytt plats på Bertil och Cecilia) så får hen 50% av maxpoäng eftersom 2 av 4 svar (Adam och Doris) är på rätt plats (1:an och 4:an).' ); ?></label>
			</div>
		</div>
		<div class="row">
			<div class="form-control radio">
				<input type="radio" name="score_type" required id="score_type__all_of" value="all_of"<?php checked($this->score_type, 'all_of'); ?>>
				<label for="score_type__all_of">Alla rätt krävs <?php AdminUtils::printTooltip( 'Maximal poäng endast om alla de rätta svaren anges. Annars noll poäng.' ); ?></label>
			</div>
		</div>
		<?php
	}
}
