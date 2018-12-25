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
    public $read_only;

    function __construct()
    {
    }

    public function get_posted_answer($form_field)
    {
        return $_POST[$form_field];
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
            case 'images':
                $field = new FieldImages($question->possible_answers ?: $question->correct_answers);
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