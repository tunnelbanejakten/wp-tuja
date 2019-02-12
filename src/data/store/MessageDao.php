<?php

namespace tuja\data\store;

use tuja\data\model\Message;
use tuja\util\Database;

class MessageDao extends AbstractDao
{
    function __construct()
    {
		parent::__construct();
		$this->table = Database::get_table('message');
    }

    function create(Message $message)
    {
        $message->validate();

        $query_template = '
            INSERT INTO ' . $this->table . ' (
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
        return $this->wpdb->query($this->wpdb->prepare($query_template,
            $message->form_question_id,
            $message->form_question_id,
            $message->group_id,
            $message->group_id,
            $message->text,
            join(',', $message->image_ids),
            $message->source,
            $message->source_message_id,
            $message->date_received != null ? $message->date_received->format('Y-m-d H:i:s') : null
        ));
    }

    function get_all()
    {
        return $this->get_objects(
	        'tuja\data\store\AbstractDao::to_message',
            'SELECT * FROM ' . $this->table . ' ORDER BY date_received');
    }

    function get_by_group($group_id)
    {
        return $this->get_objects(
	        'tuja\data\store\AbstractDao::to_message',
            'SELECT * FROM ' . $this->table . ' WHERE team_id = %d ORDER BY date_received',
            $group_id);
    }

    function get_without_group()
    {
        return $this->get_objects(
	        'tuja\data\store\AbstractDao::to_message',
            'SELECT * FROM ' . $this->table . ' WHERE team_id IS NULL ORDER BY date_received');
    }

    function exists($source, $source_message_id)
    {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $this->table . ' WHERE source = %d AND source_message_id = %s',
                $source,
                $source_message_id));
        return $count > 0;
    }
}