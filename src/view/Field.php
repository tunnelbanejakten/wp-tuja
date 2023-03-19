<?php

namespace tuja\view;

use tuja\util\Template;
use tuja\data\model\Group;

abstract class Field {
	protected $label;
	protected $formatted_label;
	protected $is_formatted_label;
	protected $hint;
	protected $formatted_hint;
	protected $is_formatted_hint;
	protected $read_only;

	public function __construct( $label, $hint = null, $read_only = false ) {
		$this->formatted_label    = Template::string( $label )->render( array(), true );
		$this->label              = $label;
		$this->is_formatted_label = '<p>' . $this->label . '</p>' !== $this->formatted_label;

		$this->formatted_hint    = Template::string( $hint )->render( array(), true );
		$this->hint              = $hint;
		$this->is_formatted_hint = '<p>' . $this->hint . '</p>' !== $this->formatted_hint;

		$this->read_only = $read_only;
	}

	protected function label_with_hint_html( string $for_field ): string {
		$html  = $this->label_html();
		$html .= $this->hint_html();
		if ( empty( $html ) ) {
			return '';
		}
		return sprintf( '<label for="%s">%s</label>', $for_field, $html );
	}

	protected function label_html() : string {
		if ( isset( $this->label ) ) {
			return $this->is_formatted_label ? $this->formatted_label : $this->label;
		}
		return '';
	}

	protected function hint_html() {
		if ( isset( $this->hint ) ) {
			return sprintf( '<small class="tuja-question-hint">%s</small>', $this->is_formatted_hint ? $this->formatted_hint : $this->hint );
		}
		return '';
	}

	abstract function render( $field_name, $answer_object, Group $group = null, $error_message = '' );

	/**
	 * Returns the data used to render the component.
	 *
	 * @param string $field_name The name of the field in $_POST
	 * @param mixed  $stored_posted_answer Data to be used if no data is found in $_POST
	 *
	 * @return mixed
	 */
	abstract function get_data( string $field_name, $stored_posted_answer, Group $group );
}
