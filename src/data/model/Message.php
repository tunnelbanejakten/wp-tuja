<?php

namespace tuja\data\model;


class Message
{
    public $id;
    public $form_question_id;
    // TODO: Rename to $group_id
    public $team_id;
    public $text;
    public $image;
    public $source;
    public $source_message_id;
    public $date_received;
    public $date_imported;

    public function validate()
    {
    }
}