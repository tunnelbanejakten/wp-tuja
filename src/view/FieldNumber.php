<?php

namespace tuja\view;

class FieldNumber extends FieldText {
	public function __construct( $label, $hint = null, bool $read_only = false ) {
		parent::__construct( $label, $hint, $read_only, [
				'type' => 'number'
			]
		);
	}

	public function get_posted_answer( $form_field ) {
		$raw = @$_POST[ $form_field ];
		if ( is_numeric( $raw ) ) {
			return floatval( $raw );
		} else {
			return $raw;
		}
	}
}