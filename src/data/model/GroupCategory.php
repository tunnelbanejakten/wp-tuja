<?php

namespace tuja\data\model;


class GroupCategory
{
    public $id;
    public $competition_id;
    public $is_crew;
    public $name;

    // TODO: 100, not 20
    public function validate()
    {
    }
}