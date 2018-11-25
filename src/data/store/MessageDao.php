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
              source_message_id,
              date_received
            ) VALUES (
                IF(%d=0, NULL, %d),
                IF(%d=0, NULL, %d),
                %s,
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
            $message->source_message_id,
            $message->date_received != null ? $message->date_received->format('Y-m-d H:i:s') : null
        ));
    }

    function get_all()
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_message',
            'SELECT * FROM message ORDER BY date_received');
    }

    function get_by_group($group_id)
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_message',
            'SELECT * FROM message WHERE team_id = %d ORDER BY date_received',
            $group_id);
    }

    function get_without_group()
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_message',
            'SELECT * FROM message WHERE team_id IS NULL ORDER BY date_received');
    }
}