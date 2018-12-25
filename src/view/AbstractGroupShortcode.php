<?php

namespace view;

use data\store\CompetitionDao;
use data\store\GroupDao;
use data\store\PersonDao;
use DateTime;
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
    protected $competition_dao;

    public function __construct($wpdb)
    {
        $this->group_dao = new GroupDao($wpdb);
        $this->person_dao = new PersonDao($wpdb);
        $this->competition_dao = new CompetitionDao($wpdb);
    }

    protected function render_field($question, $field_name, $error_message, $read_only = false): string
    {
        $field = Field::create($question);
        $field->read_only = $read_only;
        $html = $field->render($field_name);
        return sprintf('<div class="tuja-question %s">%s%s</div>',
            !empty($error_message) ? 'tuja-field-error' : '',
            $html,
            !empty($error_message) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $error_message) : '');
    }

    protected function is_create_allowed($competition_id): bool
    {
        $form = $this->competition_dao->get($competition_id);
        $now = new DateTime();
        if ($form->create_group_start != null && $form->create_group_start > $now) {
            return false;
        }
        if ($form->create_group_end != null && $form->create_group_end < $now) {
            return false;
        }
        return true;
    }

    protected function is_edit_allowed($competition_id): bool
    {
        $form = $this->competition_dao->get($competition_id);
        $now = new DateTime();
        if ($form->edit_group_start != null && $form->edit_group_start > $now) {
            return false;
        }
        if ($form->edit_group_end != null && $form->edit_group_end < $now) {
            return false;
        }
        return true;
    }
}