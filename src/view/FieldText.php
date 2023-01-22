<?php

namespace tuja\view;

use tuja\data\model\Group;

class FieldText extends Field {
	private $html_props;
	private $compact;

	function __construct( $label, $hint = null, bool $read_only = false, $html_props = [], bool $compact = false ) {
		parent::__construct( $label, $hint, $read_only );
		if ( ! isset( $html_props['type'] ) ) {
			$html_props['type'] = 'text';
		}
		$this->html_props = $html_props;
		$this->compact    = $compact;
	}

	public function get_data( string $field_name, $stored_posted_answer, Group $group ) {
		if ( isset( $_POST[ $field_name ] ) ) {
			return array( stripslashes( $_POST[ $field_name ] ) );
		} else {
			if ( is_array( $stored_posted_answer ) && isset( $stored_posted_answer[0] ) ) {
				return $stored_posted_answer;
			} else {
				return array( '' );
			}
		}
	}

	public function render( $field_name, $answer_object, Group $group = null, $error_message = '' ) {
		// TODO: This is a bit of a hack...
		if ( is_scalar( $answer_object ) ) {
			$answer_object = [ $answer_object ];
		}

		$render_id = $field_name ?: uniqid();
		$value = $this->get_data( $field_name, $answer_object, $group )[0];

		$additional_html_props = join( array_map( function ( $prop, $value ) {
			return sprintf( '%s="%s"', $prop, htmlentities( $value ) );
		}, array_keys( $this->html_props ), array_values( $this->html_props ) ) );

		if ( $this->compact && ! $this->is_formatted_label ) {
			return sprintf( '<div class="tuja-field"><input placeholder="%s" %s id="%s" name="%s" value="%s" class="tuja-%s" %s/>%s%s</div>',
				$this->label,
				$additional_html_props,
				$render_id,
				$field_name,
				htmlspecialchars( $value ),
				'fieldtext',
				$this->read_only ? ' disabled="disabled"' : '',
				! empty( $error_message ) ? sprintf( '<div class="tuja-message tuja-message-error">%s</div>', $error_message ) : '',
				$this->hint_html()
			);
		} else {
			return sprintf( '<div class="tuja-field">%s<input %s id="%s" name="%s" value="%s" class="tuja-%s" %s/>%s</div>',
				$this->label_with_hint_html( $render_id ),
				$additional_html_props,
				$render_id,
				$field_name,
				htmlspecialchars( $value ),
				'fieldtext',
				$this->read_only ? ' disabled="disabled"' : '',
				! empty( $error_message ) ? sprintf( '<div class="tuja-message tuja-message-error">%s</div>', $error_message ) : '' );
		}
	}
}