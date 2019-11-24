<?php

namespace tuja\view;

use tuja\data\model\Group;

class FieldTextMulti extends Field {

	public function get_posted_answer( $form_field ) {
		$user_answer = @$_POST[ $form_field ];

		return preg_split( "/[\s,]+/", $user_answer ) ?: [];
	}

	public function render( $field_name, $answer_object, Group $group = null, $error_message = '' ) {
		$render_id = $field_name ?: uniqid();
		$hint      = isset( $this->hint ) ? sprintf( '<small class="tuja-question-hint">%s</small>', $this->hint ) : '';

		$value = isset( $_POST[ $field_name ] )
			? $this->get_posted_answer( $field_name )
			: is_array( $answer_object )
				? $answer_object
				: [];

		return sprintf( '<div class="tuja-field"><label for="%s">%s%s</label><textarea name="%s" class="tuja-%s" %s>%s</textarea>%s</div>',
			$render_id,
			$this->label,
			$hint,
			$field_name,
			'fieldtextmulti',
			$this->read_only ? ' disabled="disabled"' : '',
			htmlspecialchars( join( ', ', $value ) ),
			! empty( $error_message ) ? sprintf( '<div class="tuja-message tuja-message-error">%s</div>', $error_message ) : '' );
	}

}