<?php

namespace tuja\view;

use tuja\data\model\Group;

class FieldTextMulti extends Field {

	public function get_data( string $field_name, $stored_posted_answer, Group $group ) {
		if ( isset( $_POST[ $field_name ] ) ) {
			return preg_split( '/[\s,]+/', stripslashes( $_POST[ $field_name ] ) ) ?: array();
		} else {
			if ( is_array( $stored_posted_answer ) ) {
				return $stored_posted_answer;
			} else {
				return array();
			}
		}
	}

	public function render( $field_name, $answer_object, Group $group = null, $error_message = '' ) {
		$render_id = $field_name ?: uniqid();
		$hint      = isset( $this->hint ) ? sprintf( '<small class="tuja-question-hint">%s</small>', $this->hint ) : '';

		$data = $this->get_data( $field_name, $answer_object, $group );

		return sprintf( '<div class="tuja-field"><label for="%s">%s%s</label><textarea name="%s" id="%s" class="tuja-%s" %s>%s</textarea>%s</div>',
			$render_id,
			$this->is_formatted_label ? $this->formatted_label : $this->label,
			$hint,
			$field_name,
			$field_name,
			'fieldtextmulti',
			$this->read_only ? ' disabled="disabled"' : '',
			htmlspecialchars( join( ', ', $data ) ),
			! empty( $error_message ) ? sprintf( '<div class="tuja-message tuja-message-error">%s</div>', $error_message ) : '' );
	}

}