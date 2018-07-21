<?php

namespace tuja\view;

include_once 'Field.php';

class FieldDropdown extends Field
{
    public $options;

    function __construct()
    {
        parent::__construct();
    }

    public function render($field_name)
    {
        $render_id = $field_name ?: uniqid();
        $selected_value = isset($_POST[$field_name]) ? $_POST[$field_name] : $this->value;
        return sprintf('<label for="%s">%s</label><select id="%s" name="%s" class="tuja-%s">%s</select>',
            $render_id,
            $this->label,
            $render_id,
            $field_name ?: $this->key,
            strtolower((new \ReflectionClass($this))->getShortName()),
            join(array_map(function ($key, $value) use ($selected_value) {
                return sprintf('<option value="%s" %s>%s</option>', $key, $key == $selected_value ? ' selected="selected"' : '', $value);
            }, array_keys($this->options), array_values($this->options))));
    }
}