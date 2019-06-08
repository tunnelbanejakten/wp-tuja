<?php

namespace tuja\view;

use tuja\data\model\Group;

abstract class Field {
	protected $key;
	protected $label;
	protected $hint;
	protected $read_only;

	// TODO: Are the Field* class constructors consistent in terms of the order of common prameters?
	public function __construct( $key, $label, $hint = null, $read_only = false ) {
		$this->key       = $key;
		$this->label     = $label;
		$this->hint      = $hint;
		$this->read_only = $read_only;
	}

	abstract function render( $field_name, $answer_object, Group $group = null );

	abstract function get_posted_answer( $form_field );

	// TODO: Delete unused code here
	/*
		static function create( Question $question ) {
			throw new Exception( 'Do not use this anymore' );
			switch ( $question->type ) {
				case 'pick_multi_one':
					$field = new FieldChoices($question->possible_answers ?: $question->correct_answers, false);
					break;
				case 'pick_multi':
					$field = new FieldChoices($question->possible_answers ?: $question->correct_answers, true);
					break;
				case 'text_multi':
					$field = new FieldTextMulti( ,);
					break;
				case 'images':
					$field = new FieldImages($question->possible_answers ?: $question->correct_answers);
					break;
				case 'pno':
					$field = new FieldText( [
						'type'        => 'tel',
						'pattern'     => Person::PNO_PATTERN,
						'placeholder' => 'ååmmddnnnn'
					], );
					break;
				case 'email':
					$field = new FieldText( [
						'type'         => 'email',
						'autocomplete' => 'autocomplete'
					], );
					break;
				case 'phone':
					$field = new FieldText( [
						'type'    => 'tel',
						'pattern' => Person::PHONE_PATTERN
					], );
					break;
				default:
					$field = new FieldText( ,);
					break;
			}
			$field->label = $question->text;
			$field->hint  = $question->text_hint;
			$field->key   = "question-" . $question->id;

			if(!is_null($question->latest_response)) {
				$field->value = $question->latest_response->submitted_answer;
			}

			return $field;
		}
	*/
}