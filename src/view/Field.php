<?php

namespace tuja\view;

use tuja\data\model\Question;
use tuja\util\Id;

class Field
{
    public $id;
    public $key;
    public $value;
    public $label;

    function __construct()
    {
    }

    static function create(Question $question)
    {
        $field = new FieldText();
        $field->label = $question->text;
        $field->key = "question-" . $question->id;
        $field->value = $question->latest_response->answer;
        return $field;
    }
}