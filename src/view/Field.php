<?php

namespace tuja\view;

use tuja\data\model\Question;

class Field
{
    public $id;
    public $key;
    // TODO: Rename to values?
    public $value;
    public $label;
    public $hint;
    public $submit_on_change;

    function __construct()
    {
    }

    static function create(Question $question)
    {
        switch ($question->type) {
            // TODO: Rename "dropdown" to "single" or "radio"
            case 'dropdown':
                $field = new FieldChoices($question->possible_answers ?: $question->correct_answers, false);
                break;
            case 'multi':
                $field = new FieldChoices($question->possible_answers ?: $question->correct_answers, true);
                break;
            default:
                $field = new FieldText();
                break;
        }
        $field->label = $question->text;
        $field->hint = $question->text_hint;
        $field->key = "question-" . $question->id;
        $field->value = $question->latest_response->answers;
        return $field;
    }
}