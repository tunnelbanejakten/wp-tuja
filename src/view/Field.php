<?php

namespace tuja\view;

use tuja\data\model\Group;

abstract class Field {
	protected $label;
	protected $hint;
	protected $read_only;

	public function __construct( $label, $hint = null, $read_only = false ) {
		$this->label     = $label;
		$this->hint      = $hint;
		$this->read_only = $read_only;
	}

	abstract function render( $field_name, $answer_object, Group $group = null, $error_message = '' );

	/**
	 * Returns the data used to render the component.
	 *
	 * @param string $field_name The name of the field in $_POST
	 * @param mixed $stored_posted_answer Data to be used if no data is found in $_POST
	 *
	 * @return mixed
	 */
	abstract function get_data( string $field_name, $stored_posted_answer );
}