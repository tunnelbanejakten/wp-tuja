<?php

namespace tuja\data\model;


use Exception;

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

    const QUESTION_GRADING_TYPE_SUBMITTED_ANSWER_IS_POINTS = "submitted_answer_is_points";
    const QUESTION_GRADING_TYPE_ONE_OF = "one_of";
    const QUESTION_GRADING_TYPE_ALL_OF = "all_of";

    const SCORING_METHODS = [
        self::QUESTION_GRADING_TYPE_ALL_OF,
        self::QUESTION_GRADING_TYPE_SUBMITTED_ANSWER_IS_POINTS,
        self::QUESTION_GRADING_TYPE_ONE_OF
    ];

    public function validate()
    {
        if (strlen($this->text) > 500) {
            throw new Exception('Frågan får inte var längre än 500 tecken.');
        }
        if (strlen($this->text_hint) > 500) {
            throw new Exception('Hjälptexten får inte var längre än 500 tecken.');
        }
        if (!empty($this->score_type) && !in_array($this->score_type, self::SCORING_METHODS)) {
            throw new Exception('Ogiltig poängberäkningsmetod.');
        }
    }

    public function score($answers)
    {
        $answers = array_map('strtolower', $answers);
        switch ($this->score_type) {
            case self::QUESTION_GRADING_TYPE_SUBMITTED_ANSWER_IS_POINTS:
                return min($this->score_max, floatval($answers[0]));
            case self::QUESTION_GRADING_TYPE_ONE_OF:
            case self::QUESTION_GRADING_TYPE_ALL_OF:
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

                $is_correct = $this->score_type == self::QUESTION_GRADING_TYPE_ALL_OF ?
                    count($answers) == count($correct_answers) && $count_correct_values == count($correct_answers) :
                    $count_correct_values > 0;
                return $is_correct ? $this->score_max : 0;
            default:
                return 0;
        }
    }

    public static function text($text, $hint = null, $response = null): Question
    {
        $question = new Question();
        $question->type = 'text';
        $question->text = $text;
        $question->text_hint = $hint;
        $question->latest_response = $response ?: new Response();
        return $question;
    }

    public static function dropdown($text, $options, $hint = null, $response = null): Question
    {
        $question = new Question();
        $question->type = 'dropdown';
        $question->text = $text;
        $question->text_hint = $hint;
        $question->latest_response = $response ?: new Response();
        $question->possible_answers = $options;
        return $question;
    }
}