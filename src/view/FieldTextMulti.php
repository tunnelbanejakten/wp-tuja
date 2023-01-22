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
		$label_and_hint = $this->label_with_hint_html( $render_id );

		$data = $this->get_data( $field_name, $answer_object, $group );

		return sprintf( '<div class="tuja-field">%s<textarea name="%s" id="%s" class="tuja-%s" %s>%s</textarea>%s</div>',
			$label_and_hint,
			$field_name,
			$field_name,
			'fieldtextmulti',
			$this->read_only ? ' disabled="disabled"' : '',
			htmlspecialchars( join( ', ', $data ) ),
			! empty( $error_message ) ? sprintf( '<div class="tuja-message tuja-message-error">%s</div>', $error_message ) : '' );
	}

}