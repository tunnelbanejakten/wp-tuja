<?php

namespace tuja\view;

use tuja\data\model\Person;
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
        return @$_POST[$form_field];
    }

    static function create(Question $question)
    {
        switch ($question->type) {
            case 'pick_one':
                $field = new FieldChoices($question->possible_answers ?: $question->correct_answers, false);
                break;
            case 'pick_multi':
                $field = new FieldChoices($question->possible_answers ?: $question->correct_answers, true);
                break;
            case 'text_multi':
                $field = new FieldTextMulti();
                break;
            case 'images':
                $field = new FieldImages($question->possible_answers ?: $question->correct_answers);
                break;
	        case 'pno':
		        $field = new FieldText( [
			        'type'        => 'tel',
			        'pattern'     => Person::PNO_PATTERN,
			        'placeholder' => 'ååmmddnnnn'
		        ] );
		        break;
	        case 'email':
		        $field = new FieldText( [
			        'type'         => 'email',
			        'autocomplete' => 'autocomplete'
		        ] );
		        break;
	        case 'phone':
		        $field = new FieldText( [
			        'type'    => 'tel',
			        'pattern' => Person::PHONE_PATTERN
		        ] );
		        break;
            default:
                $field = new FieldText();
                break;
        }
        $field->label = $question->text;
        $field->hint = $question->text_hint;
		$field->key = "question-" . $question->id;

		if(!is_null($question->latest_response)) {
			$field->value = $question->latest_response->submitted_answer;
		}
		
        return $field;
    }
}