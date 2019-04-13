<?php

namespace tuja\view;

class FieldText extends Field
{
	private $html_props;

	function __construct( $html_props = [ 'type' => 'text' ] )
    {
        parent::__construct();
	    $this->html_props = $html_props;
    }

    public function render($field_name)
    {
        $render_id = $field_name ?: uniqid();
        $hint = isset($this->hint) ? sprintf('<small class="tuja-question-hint">%s</small>', $this->hint) : '';

	    $value = isset( $_POST[ $field_name ] )
		    ? $_POST[ $field_name ]
		    : is_array( $this->value ) && isset( $this->value[0] )
			    ? $this->value[0]
			    : '';

	    return sprintf( '<div class="tuja-field"><label for="%s">%s%s</label><input %s id="%s" name="%s" value="%s" class="tuja-%s" %s/></div>',
            $render_id,
            $this->label,
            $hint,
		    join( array_map( function ( $prop, $value ) {
			    return sprintf( '%s="%s"', $prop, htmlentities( $value ) );
		    }, array_keys( $this->html_props ), array_values( $this->html_props ) ) ),
            $render_id,
            $field_name ?: $this->key,
		    htmlspecialchars( $value ),
            strtolower((new \ReflectionClass($this))->getShortName()),
            $this->read_only ? ' disabled="disabled"' : '');
    }
}