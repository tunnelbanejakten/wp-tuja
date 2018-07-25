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

    public function validate()
    {
        if (strlen($this->text) > 500) {
            throw new Exception('Frågan får inte var längre än 500 tecken.');
        }
        if (strlen($this->text_hint) > 500) {
            throw new Exception('Hjälptexten får inte var längre än 500 tecken.');
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