<?php

namespace tuja\view;

class FieldNumber extends FieldText {
	public function __construct( $label, $hint = null, bool $read_only = false ) {
		parent::__construct( $label, $hint, $read_only, [
				'type' => 'number'
			]
		);
	}

	public function get_data( string $field_name, $stored_posted_answer ) {
		if ( isset( $_POST[ $field_name ] ) ) {
			$raw = $_POST[ $field_name ];
		} else {
			if ( is_array( $stored_posted_answer ) && isset( $stored_posted_answer[0] ) ) {
				$raw = $stored_posted_answer[0];
			} else {
				$raw = $stored_posted_answer;
			}
		}
		if ( is_numeric( $raw ) ) {
			return [ floatval( $raw ) ];
		} else {
			return [ $raw ];
		}
	}
}