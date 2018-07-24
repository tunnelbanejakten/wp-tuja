<?php

namespace view;

use data\store\GroupDao;
use data\store\PersonDao;

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

    protected $person_dao;
    protected $group_dao;

    public function __construct($wpdb)
    {
        $this->group_dao = new GroupDao($wpdb);
        $this->person_dao = new PersonDao($wpdb);
    }
}