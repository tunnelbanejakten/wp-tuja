<?php

namespace tuja\view;

use tuja\data\model\Group;

class FieldText extends Field
{
	private $html_props;

	function __construct( $label, $hint = null, $read_only = false, $html_props = [ 'type' => 'text' ] ) {
		parent::__construct( $label, $hint, $read_only );
	    $this->html_props = $html_props;
    }

	public function get_posted_answer( $form_field ) {
		return [ @$_POST[ $form_field ] ];
	}

    public function render($field_name, $answer_object, Group $group = null )
    {
	    // TODO: This is a bit of a hack...
	    if ( is_scalar($answer_object) ) {
		    $answer_object = [ $answer_object ];
	    }

        $render_id = $field_name ?: uniqid();
        $hint = isset($this->hint) ? sprintf('<small class="tuja-question-hint">%s</small>', $this->hint) : '';

	    $value = isset( $_POST[ $field_name ] )
		    ? $_POST[ $field_name ]
		    : is_array( $answer_object ) && isset( $answer_object[0] )
			    ? $answer_object[0]
			    : '';

	    return sprintf( '<div class="tuja-field"><label for="%s">%s%s</label><input %s id="%s" name="%s" value="%s" class="tuja-%s" %s/></div>',
            $render_id,
            $this->label,
            $hint,
		    join( array_map( function ( $prop, $value ) {
			    return sprintf( '%s="%s"', $prop, htmlentities( $value ) );
		    }, array_keys( $this->html_props ), array_values( $this->html_props ) ) ),
            $render_id,
            $field_name,
		    htmlspecialchars( $value ),
            'fieldtext',
            $this->read_only ? ' disabled="disabled"' : '');
    }
}