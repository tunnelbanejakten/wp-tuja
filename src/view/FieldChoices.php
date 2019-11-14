<?php

namespace tuja\view;

use tuja\data\model\Group;

class FieldChoices extends Field
{
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

	public function get_posted_answer( $form_field ) {
		$user_answer = @$_POST[ $form_field ];
		if ( ! isset( $user_answer ) ) {
			// Handle special case when user has not selected any of the options to a multi-choice question.
			// We still need to store an empty array to explicitly state that the user no longer has selected
			// anything in case an option was previously selected by the user.
			return [];
		} else {
			if ( $this->is_multichoice ) {
				return $user_answer; // TODO: Why not an array?
			} else {
				return [ $user_answer ];
			}
		}
	}

	public function render( $field_name, $answer_object, Group $group = null ) {
		$render_id    = $field_name ?: uniqid();
		$hint         = isset( $this->hint ) ? sprintf( '<small class="tuja-question-hint">%s</small>', $this->hint ) : '';
		$label        = isset( $this->label ) ? $this->label : '';
		$labelAndHint = ! empty( $label ) || ! empty( $hint ) ? sprintf( '<label for="%s">%s%s</label>', $render_id, $label, $hint ) : '';

		return sprintf( '<div class="tuja-field">%s%s</div>',
			$labelAndHint,
			count( $this->options ) < self::SHORT_LIST_LIMIT ?
				$this->render_short_list( $render_id, $field_name, $answer_object ) :
				$this->render_long_list( $render_id, $field_name, $answer_object )
		);
	}

	public function render_long_list( $render_id, $field_name, $answer_object ) {
		return sprintf( '<select id="%s" name="%s" class="tuja-%s tuja-%s-longlist" %s %s %s size="%d">%s</select>',
			$render_id,
			$field_name,
			self::FIELD_TYPE,
			self::FIELD_TYPE,
			$this->is_multichoice ? ' multiple="multiple"' : '',
			$this->submit_on_change ? ' onchange="this.form.submit()"' : '',
			$this->read_only ? ' disabled="disabled"' : '',
			$this->is_multichoice ? 10 : 1,
			join( array_map( function ( $value ) use ( $field_name, $answer_object ) {
				return sprintf( '<option value="%s" %s>%s</option>', htmlspecialchars( $value ), $this->is_selected( $field_name, $value, $answer_object ) ? ' selected="selected"' : '', htmlspecialchars( $value ) );
			}, $this->options ) ) );
	}

	public function render_short_list( $render_id, $field_name, $answer_object ) {
		return join( array_map( function ( $index, $value ) use ( $render_id, $field_name, $answer_object ) {
			$id   = $render_id . '-' . $index;
			$name = $field_name;
			if ( $this->is_multichoice ) {
				// Use [] to "trick" PHP into storing selected values in an array. Requires that other parts of the code handles both scalars and arrays.
				$name .= '[]';
			}

			return sprintf( '<div class="tuja-%s-%s"><input type="%s" name="%s" value="%s" class="tuja-%s tuja-%s-shortlist" id="%s" %s %s %s/><label for="%s">%s</label></div>',
				self::FIELD_TYPE,
				$this->is_multichoice ? 'checkbox' : 'radiobutton',
				$this->is_multichoice ? 'checkbox' : 'radio',
				$name,
				htmlspecialchars( $value ),
				self::FIELD_TYPE,
				self::FIELD_TYPE,
				$id,
				$this->is_selected( $field_name, $value, $answer_object ) ? ' checked="checked"' : '',
				$this->submit_on_change ? ' onchange="this.form.submit()"' : '',
				$this->read_only ? ' disabled="disabled"' : '',
				$id,
				htmlspecialchars( $value ) );
		}, array_keys( $this->options ), array_values( $this->options ) ) );
	}

	private function is_selected( $field_name, $value, $answer_object ) {
		$selected_values = [];
		if ( $this->is_multichoice ) {
			if ( isset( $_POST[ $field_name ] ) && is_array( $_POST[ $field_name ] ) ) {
				$selected_values = $_POST[ $field_name ];
			} elseif ( is_array( $answer_object ) ) {
				$selected_values = $answer_object;
			}
		} else {
			if ( isset( $_POST[ $field_name ] ) && ! is_array( $_POST[ $field_name ] ) ) {
				$selected_values = array( $_POST[ $field_name ] );
			} else {
				$selected_values = @$answer_object[0] ? array( $answer_object[0] ) : array();
			}
		}

		return in_array( $value, $selected_values );
	}
}