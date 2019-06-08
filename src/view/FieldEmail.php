<?php

namespace tuja\view;

class FieldEmail extends FieldText {
	public function __construct( $label, $hint = null, bool $read_only = false ) {
		parent::__construct( $label, $hint, $read_only, [
				'type'         => 'email',
				'autocomplete' => 'autocomplete'
			]
		);
	}
}