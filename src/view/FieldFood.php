<?php

namespace tuja\view;

use tuja\data\model\Group;

class FieldFood extends Field {
	const FIELD_TYPE = 'fieldfood';

	private $options;
	private $compact;
	private $is_custom_options_allowed;
	private $strings;

	const FORM_FIELD_SEPARATOR    = '__'; // Needs to match what's used by GroupPeopleEditor.
	const CUSTOM_OPTION_SEPARATOR = ',';
	const OPTIONS_NAME            = 'options';
	const TOGGLE_NAME             = 'toggle';
	const TOGGLE_VALUE_YES        = 'yes';
	const TOGGLE_VALUE_NO         = 'no';

	public function __construct( $label, $hint = null, $read_only = false, $compact = false, $options = array(), $strings = array(), $is_custom_options_allowed = false ) {
		parent::__construct( $label, $hint, $read_only );
		$this->options                   = $options;
		$this->compact                   = $compact;
		$this->is_custom_options_allowed = $is_custom_options_allowed;
		$this->strings                   = array_merge(
			array(
				'toggle_on_label'           => 'Yes',
				'toggle_off_label'          => 'No',
				'custom_option_placeholder' => 'Other...',
			),
			$strings
		);
	}

	public function get_data( string $field_name, $stored_posted_answer, Group $group ) {
		if ( self::TOGGLE_VALUE_NO === @$_POST[ $field_name . self::FORM_FIELD_SEPARATOR . self::TOGGLE_NAME ] ) {
			return array( '' );
		}

		if ( isset( $_POST[ $field_name . self::FORM_FIELD_SEPARATOR . self::OPTIONS_NAME ] ) && is_array( $_POST[ $field_name . self::FORM_FIELD_SEPARATOR . self::OPTIONS_NAME ] ) ) {
			// Already array because of [] trick in field name.
			return array_map( 'trim', array_map( 'stripslashes', $_POST[ $field_name . self::FORM_FIELD_SEPARATOR . self::OPTIONS_NAME ] ) );
		} elseif ( is_array( $stored_posted_answer ) && isset( $stored_posted_answer[0] ) ) {
			return array_map( 'trim', explode( self::CUSTOM_OPTION_SEPARATOR, $stored_posted_answer[0] ) );
		}

		// Handle special case when user has not selected any of the options to a multi-choice question.
		// We still need to store an empty array to explicitly state that the user no longer has selected
		// anything in case an option was previously selected by the user.
		return array( '' );
	}

	public static function toggle_option_html( string $field_name, string $value, string $label, bool $is_selected ) {
		$id = $field_name . self::FORM_FIELD_SEPARATOR . self::TOGGLE_NAME . '-' . $value;
		return sprintf(
			'<input type="radio" name="%s" %s id="%s" value="%s" onchange="this.parentNode.parentNode.classList.toggle(\'tuja-%s-notspecified\')"><label for="%s">%s</label>',
			$field_name . self::FORM_FIELD_SEPARATOR . self::TOGGLE_NAME,
			$is_selected ? 'checked' : '',
			$id,
			$value,
			self::FIELD_TYPE,
			$id,
			$label
		);
	}

	public function render( $field_name, $answer_object, Group $group = null, $error_message = '' ) {
		$render_id      = $field_name ?: uniqid();
		$hint           = isset( $this->hint ) ? sprintf( '<small class="tuja-question-hint">%s</small>', $this->hint ) : '';
		$label          = isset( $this->label )
				? ( $this->is_formatted_label ? $this->formatted_label : $this->label )
				: '';
		$label_and_hint = ! empty( $label ) || ! empty( $hint ) ? sprintf( '<label for="%s">%s%s</label>', $render_id, $label, $hint ) : '';

		$head = $this->compact ? '' : $label_and_hint;
		$tail = $this->compact ? $hint : '';

		$data = $this->get_data( $field_name, $answer_object, $group );

		$is_value_specified = count( $data ) > 0 && '' !== $data[0];

		$yes_no_html = sprintf(
			'<div class="tuja-%s-%s"> %s <br> %s </div>',
			self::FIELD_TYPE,
			self::TOGGLE_NAME,
			self::toggle_option_html( $field_name, self::TOGGLE_VALUE_NO, $this->strings['toggle_off_label'], ! $is_value_specified ),
			self::toggle_option_html( $field_name, self::TOGGLE_VALUE_YES, $this->strings['toggle_on_label'], $is_value_specified ),
		);

		$custom_option = join( self::CUSTOM_OPTION_SEPARATOR . ' ', array_diff( $data, $this->options ) );

		$custom_option_html = $this->is_custom_options_allowed ? sprintf(
			'<div class="tuja-%s-customoption"><input type="text" name="%s" value="%s" class="tuja-%s tuja-%s-customoption" placeholder="%s" /></div>',
			self::FIELD_TYPE,
			$field_name . self::FORM_FIELD_SEPARATOR . self::OPTIONS_NAME . '[]',
			$custom_option,
			self::FIELD_TYPE,
			self::FIELD_TYPE,
			$this->strings['custom_option_placeholder']
		) : '';

		return sprintf(
			'<div class="tuja-field tuja-field-%s %s">%s%s<div class="tuja-%s-optionscontainer"><div class="tuja-%s-optionscontainer-overlay"></div><div class="tuja-%s-checkboxes">%s</div>%s</div>%s %s</div>',
			self::FIELD_TYPE,
			! $is_value_specified ? 'tuja-' . self::FIELD_TYPE . '-notspecified' : '',
			$head,
			$yes_no_html,
			self::FIELD_TYPE,
			self::FIELD_TYPE,
			self::FIELD_TYPE,
			$this->render_list( $render_id, $field_name, $data ),
			$custom_option_html,
			! empty( $error_message ) ? sprintf( '<div class="tuja-message tuja-message-error">%s</div>', $error_message ) : '',
			$tail
		);
	}

	public function render_list( $render_id, $field_name, $data ) {
		return join(
			array_map(
				function ( $option_index, $option_value ) use ( $render_id, $field_name, $data ) {
					$id = $render_id . '-' . $option_index;

					return sprintf(
						'<div class="tuja-%s-%s"><input type="%s" name="%s" value="%s" class="tuja-%s tuja-%s-shortlist" id="%s" %s %s/><label for="%s">%s</label></div>',
						self::FIELD_TYPE,
						'checkbox',
						'checkbox',
						$field_name . self::FORM_FIELD_SEPARATOR . self::OPTIONS_NAME . '[]',
						htmlspecialchars( $option_value ),
						self::FIELD_TYPE,
						self::FIELD_TYPE,
						$id,
						in_array( $option_value, $data ) ? ' checked="checked"' : '',
						$this->read_only ? ' disabled="disabled"' : '',
						$id,
						htmlspecialchars( $option_value )
					);
				},
				array_keys( $this->options ),
				array_values( $this->options )
			)
		);
	}
}
