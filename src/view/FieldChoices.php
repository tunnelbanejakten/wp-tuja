<?php

namespace tuja\view;

use tuja\data\model\Group;

class FieldChoices extends Field {
	const FIELD_TYPE = 'fieldchoices';
	private $options;
	private $is_multichoice;
	private $submit_on_change;

	const SHORT_LIST_LIMIT = 5;

	public function __construct( $label, $hint = null, $read_only = false, $options = [], $is_multichoice = false, $submit_on_change = false ) {
		parent::__construct( $label, $hint, $read_only );
		$this->options          = $options;
		$this->is_multichoice   = $is_multichoice;
		$this->submit_on_change = $submit_on_change;
	}

	public function get_data( string $field_name, $stored_posted_answer, Group $group ) {
		if ( $this->is_multichoice ) {
			if ( isset( $_POST[ $field_name ] ) && is_array( $_POST[ $field_name ] ) ) {
				return array_map( 'stripslashes', $_POST[ $field_name ] ); // Already array because of [] trick in field name.
			} elseif ( is_array( $stored_posted_answer ) ) {
				return $stored_posted_answer;
			}
		} else {
			if ( isset( $_POST[ $field_name ] ) && ! is_array( $_POST[ $field_name ] ) ) {
				return array( stripslashes( $_POST[ $field_name ] ) );
			} else {
				return @$stored_posted_answer[0] ? array( $stored_posted_answer[0] ) : array();
			}
		}

		// Handle special case when user has not selected any of the options to a multi-choice question.
		// We still need to store an empty array to explicitly state that the user no longer has selected
		// anything in case an option was previously selected by the user.
		return array();
	}

	public function render( $field_name, $answer_object, Group $group = null, $error_message = '' ) {
		$render_id    = $field_name ?: uniqid();
		$label_html   = array();
		if ( isset( $this->label ) ) {
			$label_html[] = $this->is_formatted_label ? $this->formatted_label : $this->label;
		}
		if ( isset( $this->hint ) ) {
			$label_html[] = sprintf( '<small class="tuja-question-hint">%s</small>', $this->is_formatted_hint ? $this->formatted_hint : $this->hint );
		}
		$label_and_hint = ! empty( $label_html ) ? sprintf( '<label for="%s">%s</label>', $render_id, join( $label_html ) ) : '';

		$data = $this->get_data( $field_name, $answer_object, $group );

		return sprintf( '<div class="tuja-field">%s%s%s</div>',
			$label_and_hint,
			count( $this->options ) < self::SHORT_LIST_LIMIT ?
				$this->render_short_list( $render_id, $field_name, $data ) :
				$this->render_long_list( $render_id, $field_name, $data ),
			! empty( $error_message ) ? sprintf( '<div class="tuja-message tuja-message-error">%s</div>', $error_message ) : ''
		);
	}

	public function render_long_list( $render_id, $field_name, $data ) {
		return sprintf( '<select id="%s" name="%s" class="tuja-%s tuja-%s-longlist" %s %s %s size="%d">%s</select>',
			$render_id,
			// Use [] to "trick" PHP into storing selected values in an array. Requires that other parts of the code handles both scalars and arrays.
			// TODO: Always store in array (makes like easier)
			$field_name . ($this->is_multichoice ? '[]' : ''),
			self::FIELD_TYPE,
			self::FIELD_TYPE,
			$this->is_multichoice ? ' multiple="multiple"' : '',
			$this->submit_on_change ? ' onchange="this.form.submit()"' : '',
			$this->read_only ? ' disabled="disabled"' : '',
			$this->is_multichoice ? 10 : 1,
			join( array_map( function ( $option ) use ( $data ) {
				return sprintf( '<option value="%s" %s>%s</option>',
					htmlspecialchars( $option ),
					in_array( $option, $data ) ? ' selected="selected"' : '',
					htmlspecialchars( $option ) );
			}, $this->options ) ) );
	}

	public function render_short_list( $render_id, $field_name, $data ) {
		return join( array_map( function ( $option_index, $option_value ) use ( $render_id, $field_name, $data ) {
			$id = $render_id . '-' . $option_index;

			return sprintf( '<div class="tuja-%s-%s"><input type="%s" name="%s" value="%s" class="tuja-%s tuja-%s-shortlist" id="%s" %s %s %s/><label for="%s">%s</label></div>',
				self::FIELD_TYPE,
				$this->is_multichoice ? 'checkbox' : 'radiobutton',
				$this->is_multichoice ? 'checkbox' : 'radio',
				// Use [] to "trick" PHP into storing selected values in an array. Requires that other parts of the code handles both scalars and arrays.
				// TODO: Always store in array (makes like easier)
				$field_name . ($this->is_multichoice ? '[]' : ''),
				htmlspecialchars( $option_value ),
				self::FIELD_TYPE,
				self::FIELD_TYPE,
				$id,
				in_array( $option_value, $data ) ? ' checked="checked"' : '',
				$this->submit_on_change ? ' onchange="this.form.submit()"' : '',
				$this->read_only ? ' disabled="disabled"' : '',
				$id,
				htmlspecialchars( $option_value ) );
		}, array_keys( $this->options ), array_values( $this->options ) ) );
	}
}