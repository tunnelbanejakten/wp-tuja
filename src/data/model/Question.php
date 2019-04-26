<?php

namespace tuja\data\model;


class Question
{
    public $id;
    public $question_group_id;
    public $type;
    public $correct_answers;
    public $possible_answers;
    public $text;
    public $sort_order;
    public $text_hint;
    public $latest_response;
    public $score_type;
    public $score_max;

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

	const QUESTION_TYPE_TEXT = 'text';
	const QUESTION_TYPE_NUMBER = 'number';
	const QUESTION_TYPE_HEADER = 'header';
	const QUESTION_TYPE_PICK_ONE = 'pick_one';
	const QUESTION_TYPE_PICK_MULTI = 'pick_multi';
	const QUESTION_TYPE_IMAGES = 'images';
	const QUESTION_TYPE_TEXT_MULTI = 'text_multi';

	const VALID_TYPES = [
		self::QUESTION_TYPE_TEXT,
		self::QUESTION_TYPE_NUMBER,
		self::QUESTION_TYPE_HEADER,
		self::QUESTION_TYPE_PICK_ONE,
		self::QUESTION_TYPE_PICK_MULTI,
		self::QUESTION_TYPE_IMAGES,
		self::QUESTION_TYPE_TEXT_MULTI
	];

    const SCORING_METHODS = [
        self::GRADING_TYPE_ALL_OF,
	    self::GRADING_TYPE_UNORDERED_PERCENT_OF,
	    self::GRADING_TYPE_ORDERED_PERCENT_OF,
        self::GRADING_TYPE_ONE_OF
    ];

	public function validate()
    {
        if (strlen($this->text) > 65000) {
	        throw new ValidationException('text', 'Frågan är för lång.');
        }
        if (strlen($this->text_hint) > 65000) {
	        throw new ValidationException('text_hint', 'Hjälptexten är för lång.');
        }
        if (!empty($this->score_type) && !in_array($this->score_type, self::SCORING_METHODS)) {
	        throw new ValidationException('score_type', 'Ogiltig poängberäkningsmetod.');
		}
		if(!in_array($this->type, self::VALID_TYPES)) {
			throw new ValidationException('type', 'Ogiltig frågetyp.');
		}
    }

    public function score($answers)
    {
	    $answers         = array_map( 'strtolower', $answers );
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

			    return !empty($this_answer_percents_correct) ? max( $this_answer_percents_correct ) : null;
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

	private static function create( $type, $text, $answer, $hint ): Question {
		$question                  = new Question();
		$question->type            = $type;
		$question->text            = $text;
		$question->text_hint       = $hint;
		$question->latest_response = new Response( isset( $answer ) && ! empty( $answer ) ? [ $answer ] : [] );

		return $question;
	}

	public static function text( $text, $hint = null, $answer = null ): Question {
		return self::create( 'text', $text, $answer, $hint );
    }

	public static function email( $text, $hint = null, $answer = null ): Question {
		return self::create( 'email', $text, $answer, $hint );
	}

	public static function phone( $text, $hint = null, $answer = null ): Question {
		return self::create( 'phone', $text, $answer, $hint );
	}

	public static function pno( $text, $hint = null, $answer = null ): Question {
		return self::create( 'pno', $text, $answer, $hint );
    }

    public static function dropdown($text, $options, $hint = null, $answer = null): Question
    {
        $question = new Question();
        $question->type = self::QUESTION_TYPE_PICK_ONE;
        $question->text = $text;
        $question->text_hint = $hint;
        $question->possible_answers = $options;
        $question->latest_response = new Response(isset($answer) && !empty($answer) ? [$answer] : []);
        return $question;
    }

    public static function checkboxes($text, $options, $hint = null, $answer = null): Question
    {
        $question = new Question();
        $question->type = self::QUESTION_TYPE_PICK_MULTI;
        $question->text = $text;
        $question->text_hint = $hint;
        $question->possible_answers = $options;
        $question->latest_response = new Response(isset($answer) && !empty($answer) ? $answer : []);
        return $question;
    }
}