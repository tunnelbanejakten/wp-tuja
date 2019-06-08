<?php

namespace tuja\view;

use tuja\data\model\Person;

class FieldPhone extends FieldText {
	public function __construct( $label, $hint = null, bool $read_only = false ) {
		parent::__construct( $label, $hint, $read_only, [
				'type'    => 'tel',
				'pattern' => Person::PHONE_PATTERN
			]
		);
	}
}