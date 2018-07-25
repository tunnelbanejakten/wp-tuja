<?php

namespace tuja\view;

use tuja\data\model\Question;

class Field
{
    public $id;
    public $key;
    public $value;
    public $label;
    public $hint;

    function __construct()
    {
    }

    static function create(Question $question)
    {
        switch ($question->type) {
            case 'dropdown':
                $field = new FieldDropdown();
                $field->options = $question->possible_answers ?: $question->correct_answers;
                break;
            default:
                $field = new FieldText();
                break;
        }
        $field->label = $question->text;
        $field->hint = $question->text_hint;
        $field->key = "question-" . $question->id;
        $field->value = $question->latest_response->answer;
        return $field;
    }
}