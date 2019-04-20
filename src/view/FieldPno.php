<?php

namespace tuja\view;

use ReflectionClass;
use tuja\data\model\Group;

class FieldPno extends Field
{
	function __construct( )
    {
        parent::__construct();
    }

    public function render($field_name, Group $group )
    {
        $render_id = $field_name ?: uniqid();
        $hint = isset($this->hint) ? sprintf('<small class="tuja-question-hint">%s</small>', $this->hint) : '';

	    $field_slug = strtolower( ( new ReflectionClass( $this ) )->getShortName() );
	    $html_date  = sprintf( '<input type="text" id="%s" name="%s_date" value="%s" class="tuja-%s tuja-%s-date" %s placeholder="ååmmdd"/>',
		    $render_id,
            $field_name ?: $this->key,
            htmlspecialchars(isset($_POST[$field_name]) ? $_POST[$field_name] : $this->value[0]),
		    $field_slug,
		    $field_slug,
            $this->read_only ? ' disabled="disabled"' : '' );
	    $html_extra = sprintf( '<input type="text" name="%s_extra" value="%s" class="tuja-%s tuja-%s-extra" %s placeholder="nnnn"/>',
            $field_name ?: $this->key,
            htmlspecialchars(isset($_POST[$field_name]) ? $_POST[$field_name] : $this->value[0]),
		    $field_slug,
		    $field_slug,
            $this->read_only ? ' disabled="disabled"' : '' );
	    return sprintf( '<div class="tuja-field"><label for="%s">%s%s</label>%s - %s</div>',
            $render_id,
            $this->label,
            $hint,
		    $html_date,
		    $html_extra );
    }
}