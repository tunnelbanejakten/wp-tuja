<?php

namespace tuja\view;

class FieldChoices extends Field
{
    public $options;
    public $is_multichoice;

    const SHORT_LIST_LIMIT = 5;

    public function __construct($options, $is_multichoice)
    {
        parent::__construct();
        $this->options = $options;
        $this->is_multichoice = $is_multichoice;
    }

    public function get_posted_answer($form_field)
    {
        $user_answer = parent::get_posted_answer($form_field);
        if ($this->is_multichoice && !isset($user_answer)) {
            // Handle special case when user has not selected any of the options to a multi-choice question.
            // We still need to store an empty array to explicitly state that the user no longer has selected
            // anything in case an option was previously selected by the user.
            return array();
        }
        return $user_answer;
    }

    public function render($field_name)
    {
        $render_id = $field_name ?: uniqid();
        $hint = isset($this->hint) ? sprintf('<br><span class="tuja-question-hint">%s</span>', $this->hint) : '';
        return sprintf('<div class="tuja-field"><label for="%s">%s%s</label>%s</div>',
            $render_id,
            $this->label,
            $hint,
            count($this->options) < self::SHORT_LIST_LIMIT ?
                $this->render_short_list($render_id, $field_name) :
                $this->render_long_list($render_id, $field_name)
        );
    }

    public function render_long_list($render_id, $field_name)
    {
        return sprintf('<select id="%s" name="%s" class="tuja-%s tuja-%s-longlist" %s %s %s size="%d">%s</select>',
            $render_id,
            $field_name ?: $this->key,
            strtolower((new \ReflectionClass($this))->getShortName()),
            strtolower((new \ReflectionClass($this))->getShortName()),
            $this->is_multichoice ? ' multiple="multiple"' : '',
            $this->submit_on_change ? ' onchange="this.form.submit()"' : '',
            $this->read_only ? ' disabled="disabled"' : '',
            $this->is_multichoice ? 10 : 1,
            join(array_map(function ($value) use ($field_name) {
                return sprintf('<option value="%s" %s>%s</option>', htmlspecialchars($value), $this->is_selected($field_name, $value) ? ' selected="selected"' : '', htmlspecialchars($value));
            }, $this->options)));
    }

    public function render_short_list($render_id, $field_name)
    {
        return join(array_map(function ($index, $value) use ($render_id, $field_name) {
            $id = $render_id . '-' . $index;
            $name = $field_name ?: $this->key;
            if ($this->is_multichoice) {
                // Use [] to "trick" PHP into storing selected values in an array. Requires that other parts of the code handles both scalars and arrays.
                $name .= '[]';
            }
            return sprintf('<div class="tuja-%s-%s"><input type="%s" name="%s" value="%s" class="tuja-%s tuja-%s-shortlist" id="%s" %s %s %s/><label for="%s">%s</label></div>',
                strtolower((new \ReflectionClass($this))->getShortName()),
                $this->is_multichoice ? 'checkbox' : 'radiobutton',
                $this->is_multichoice ? 'checkbox' : 'radio',
                $name,
                htmlspecialchars($value),
                strtolower((new \ReflectionClass($this))->getShortName()),
                strtolower((new \ReflectionClass($this))->getShortName()),
                $id,
                $this->is_selected($field_name, $value) ? ' checked="checked"' : '',
                $this->submit_on_change ? ' onchange="this.form.submit()"' : '',
                $this->read_only ? ' disabled="disabled"' : '',
                $id,
                htmlspecialchars($value));
        }, array_keys($this->options), array_values($this->options)));
    }

    private function is_selected($field_name, $value)
    {
        $selected_values = [];
        if ($this->is_multichoice) {
            if (is_array($_POST[$field_name])) {
                $selected_values = $_POST[$field_name];
            } elseif (is_array($this->value)) {
                $selected_values = $this->value;
            }
        } else {
            if (isset($_POST[$field_name]) && !is_array($_POST[$field_name])) {
                $selected_values = array($_POST[$field_name]);
            } else {
                $selected_values = array($this->value[0]);
            }
        }
        return in_array($value, $selected_values);
    }
}