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
        $hint = isset($this->hint) ? sprintf('<br><span class="tuja-question-hint">%s</span>', $this->hint) : '';
        return sprintf('<div class="tuja-field"><label for="%s">%s%s</label><input type="text" id="%s" name="%s" value="%s" class="tuja-%s" /></div>',
            $render_id,
            $this->label,
            $hint,
            $render_id,
            $field_name ?: $this->key,
            isset($_POST[$field_name]) ? $_POST[$field_name] : $this->value,
            strtolower((new \ReflectionClass($this))->getShortName()));
    }
}