<?php

namespace tuja\data\model;


class Message
{
    public $id;
    public $form_question_id;
    public $group_id;
    public $text;
    public $image_ids;
    public $source;
    public $source_message_id;
    public $date_received;
    public $date_imported;

    public function validate()
    {
    }
}