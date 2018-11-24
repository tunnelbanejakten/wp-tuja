<?php

namespace data\store;

use tuja\data\model\Message;

class MessageDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function create(Message $message)
    {
        $message->validate();

        $query_template = '
            INSERT INTO message (
              form_question_id,
              team_id,
              text,
              image,
              source,
              source_message_id
            ) VALUES (
                IF(%d=0, NULL, %d),
                IF(%d=0, NULL, %d),
                %s,
                %s,
                %s,
                %s
            )';
//        var_dump($message);
        return $this->wpdb->query($this->wpdb->prepare($query_template,
            $message->form_question_id,
            $message->form_question_id,
            $message->team_id,
            $message->team_id,
            $message->text,
            $message->image,
            $message->source,
            $message->source_message_id
        ));
    }

    function get_all()
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_message',
            'SELECT * FROM message ORDER BY id');
    }

}