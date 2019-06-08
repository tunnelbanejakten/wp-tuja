<?php

namespace tuja\view;

use tuja\data\model\Group;

abstract class Field {
	protected $label;
	protected $hint;
	protected $read_only;

	// TODO: Are the Field* class constructors consistent in terms of the order of common prameters?
	public function __construct( $label, $hint = null, $read_only = false ) {
		$this->label     = $label;
		$this->hint      = $hint;
		$this->read_only = $read_only;
	}

	abstract function render( $field_name, $answer_object, Group $group = null );

	abstract function get_posted_answer( $form_field );
}