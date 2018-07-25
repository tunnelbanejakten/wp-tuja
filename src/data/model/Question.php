<?php

namespace tuja\data\model;


use Exception;

class Question
{
    public $id;
    public $form_id;
    public $type;
    public $answer;
    public $text;
    public $sort_order;
    public $text_hint;
    public $latest_response;

    public function set_answer_one_of($valid_responses)
    {
        $this->answer = json_encode(array(
            'validation' => 'one_of',
            'values' => $valid_responses
        ));
    }

    public function set_answer_one_of_these($valid_response, $selectable_responses)
    {
        $this->answer = json_encode(array(
            'validation' => 'one_of',
            'values' => array($valid_response),
            'options' => $selectable_responses
        ));
    }

    public function validate()
    {
        if (strlen($this->text) > 500) {
            throw new Exception('Frågan får inte var längre än 500 tecken.');
        }
        if (strlen($this->text_hint) > 500) {
            throw new Exception('Hjälptexten får inte var längre än 500 tecken.');
        }
        if (strlen($this->answer) > 500) {
            throw new Exception('Svaret får inte var längre än 500 tecken.');
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
        $question->set_answer_one_of($options);
        return $question;
    }
}