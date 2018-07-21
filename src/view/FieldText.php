<?php

namespace tuja\view;

include_once 'Field.php';

class FieldText extends Field
{
    function __construct()
    {
        parent::__construct();
    }

    public function render($field_name)
    {
        $render_id = $field_name ?: uniqid();
        return sprintf('<label for="%s">%s</label><input type="text" id="%s" name="%s" value="%s" class="tuja-%s" />',
            $render_id,
            $this->label,
            $render_id,
            $field_name ?: $this->key,
            isset($_POST[$field_name]) ? $_POST[$field_name] : $this->value,
            strtolower((new \ReflectionClass($this))->getShortName()));
    }
}