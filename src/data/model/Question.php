<?php

namespace tuja\data\model;


class Question
{
    public $id;
    public $form_id;
    public $type;
    public $correct_answers;
    public $possible_answers;
    public $text;
    public $sort_order;
    public $text_hint;
    public $latest_response;
    public $score_type;
    public $score_max;

    const GRADING_TYPE_ONE_OF = "one_of";
	const GRADING_TYPE_ALL_OF = "all_of";
	const VALID_TYPES = ['text', 'number', 'header', 'pick_one', 'pick_multi', 'images'];

    const SCORING_METHODS = [
        self::GRADING_TYPE_ALL_OF,
        self::GRADING_TYPE_ONE_OF
    ];

	public function validate()
    {
        if (strlen($this->text) > 500) {
	        throw new ValidationException('text', 'Frågan får inte var längre än 500 tecken.');
        }
        if (strlen($this->text_hint) > 500) {
	        throw new ValidationException('text_hint', 'Hjälptexten får inte var längre än 500 tecken.');
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
        $answers = array_map('strtolower', $answers);
        switch ($this->score_type) {
            case self::GRADING_TYPE_ONE_OF:
            case self::GRADING_TYPE_ALL_OF:
                $correct_answers = array_map('strtolower', $this->correct_answers);

                $answers_percent_correct = array_map(function ($answer) use ($correct_answers) {
                    $this_answer_percents_correct = array_map(function ($correct_answer) use ($answer) {
                        $percent = 0;
                        similar_text($answer, $correct_answer, $percent);
                        return $percent;
                    }, $correct_answers);
                    return max($this_answer_percents_correct);
                }, $answers);

                $count_correct_values = count(array_filter($answers_percent_correct,
                    function ($percent) {
                        return $percent > 80;
                    }));

                $is_correct = $this->score_type == self::GRADING_TYPE_ALL_OF ?
                    count($answers) == count($correct_answers) && $count_correct_values == count($correct_answers) :
                    $count_correct_values > 0;
                return $is_correct ? $this->score_max : 0;
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
        $question->type = 'pick_one';
        $question->text = $text;
        $question->text_hint = $hint;
        $question->possible_answers = $options;
        $question->latest_response = new Response(isset($answer) && !empty($answer) ? [$answer] : []);
        return $question;
    }

    public static function checkboxes($text, $options, $hint = null, $answer = null): Question
    {
        $question = new Question();
        $question->type = 'pick_multi';
        $question->text = $text;
        $question->text_hint = $hint;
        $question->possible_answers = $options;
        $question->latest_response = new Response(isset($answer) && !empty($answer) ? $answer : []);
        return $question;
    }
}