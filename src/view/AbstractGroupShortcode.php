<?php

namespace view;

use data\store\GroupCategoryDao;
use data\store\GroupDao;
use data\store\PersonDao;
use tuja\view\Field;

class AbstractGroupShortcode
{
    const ACTION_BUTTON_NAME = 'tuja-action';
    const ACTION_NAME_SAVE = 'save';

    const FIELD_PREFIX_PERSON = 'tuja-person__';
    const FIELD_PREFIX_GROUP = 'tuja-group__';
    const FIELD_GROUP_NAME = self::FIELD_PREFIX_GROUP . 'name';
    const FIELD_GROUP_AGE = self::FIELD_PREFIX_GROUP . 'age';
    const FIELD_PERSON_NAME = self::FIELD_PREFIX_PERSON . 'name';
    const FIELD_PERSON_EMAIL = self::FIELD_PREFIX_PERSON . 'email';
    const FIELD_PERSON_PHONE = self::FIELD_PREFIX_PERSON . 'phone';

    protected $person_dao;
    protected $group_dao;
    private $is_crew_form;

    public function __construct($wpdb, $is_crew_form)
    {
        $this->group_dao = new GroupDao($wpdb);
        $this->person_dao = new PersonDao($wpdb);
        $this->category_dao = new GroupCategoryDao($wpdb);
        $this->is_crew_form = $is_crew_form;
    }

    protected function render_field($question, $field_name, $error_message): string
    {
        $html_field = Field::create($question)->render($field_name);
        return sprintf('<div class="tuja-question %s">%s%s</div>',
            !empty($error_message) ? 'tuja-field-error' : '',
            $html_field,
            !empty($error_message) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $error_message) : '');
    }

    protected function get_categories($competition_id): array
    {
        $filter_crew_categories = $this->is_crew_form == true;
        $categories = array_filter($this->category_dao->get_all_in_competition($competition_id), function ($category) use ($filter_crew_categories) {
            return $category->is_crew == $filter_crew_categories;
        });
        return $categories;
    }
}