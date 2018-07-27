<?php

namespace view;

use data\store\GroupDao;

class GroupNameShortcode
{
    private $group_dao;

    public function __construct($wpdb, $group_key)
    {
        $this->group_key = $group_key;
        $this->group_dao = new GroupDao($wpdb);
    }

    public function render(): String
    {
        $group = $this->group_dao->get_by_key($this->group_key);
        if ($group !== false && !empty($group->name)) {
            return $group->name;
        } else {
            return '"lag utan namn"';
        }
    }

}