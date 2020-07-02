<?php

namespace tuja\view;

use tuja\data\model\Group;

class FieldTextMulti extends Field {

	public function get_data( string $field_name, $stored_posted_answer ) {
		if ( isset( $_POST[ $field_name ] ) ) {
			return preg_split( "/[\s,]+/", $_POST[ $field_name ] ) ?: [];
		} else {
			if ( is_array( $stored_posted_answer ) ) {
				return $stored_posted_answer;
			} else {
				return [];
			}
		}
	}

	public function render( $field_name, $answer_object, Group $group = null, $error_message = '' ) {
		$render_id = $field_name ?: uniqid();
		$hint      = isset( $this->hint ) ? sprintf( '<small class="tuja-question-hint">%s</small>', $this->hint ) : '';

		$data = $this->get_data( $field_name, $answer_object );

		return sprintf( '<div class="tuja-field"><label for="%s">%s%s</label><textarea name="%s" id="%s" class="tuja-%s" %s>%s</textarea>%s</div>',
			$render_id,
			$this->label,
			$hint,
			$field_name,
			$field_name,
			'fieldtextmulti',
			$this->read_only ? ' disabled="disabled"' : '',
			htmlspecialchars( join( ', ', $data ) ),
			! empty( $error_message ) ? sprintf( '<div class="tuja-message tuja-message-error">%s</div>', $error_message ) : '' );
	}

}