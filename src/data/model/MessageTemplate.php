<?php

namespace tuja\data\model;


class MessageTemplate
{
    public $id;
    public $competition_id;
    public $name;
    public $subject;
    public $body;

    public function validate()
    {
    }
}