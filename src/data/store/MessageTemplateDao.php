<?php

namespace data\store;

use data\model\ValidationException;
use tuja\data\model\MessageTemplate;

class MessageTemplateDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function create(MessageTemplate $message_template)
    {
        $message_template->validate();

        if ($this->exists($message_template->name, $message_template->id)) {
            throw new ValidationException('name', 'Det finns redan en mall med detta namn.');
        }

        $affected_rows = $this->wpdb->insert('message_template',
            array(
                'competition_id' => $message_template->competition_id,
                'name' => $message_template->name,
                'subject' => $message_template->subject,
                'body' => $message_template->body
            ),
            array(
                '%s',
                '%s',
                '%s',
                '%s'
            ));
        $success = $affected_rows !== false && $affected_rows === 1;

        return $success ? $this->wpdb->insert_id : false;
    }

    function update(MessageTemplate $message_template)
    {
        $message_template->validate();

        if ($this->exists($message_template->name, $message_template->id)) {
            throw new ValidationException('name', 'Det finns redan en mall med detta namn.');
        }

        return $this->wpdb->update('message_template',
            array(
                'name' => $message_template->name,
                'subject' => $message_template->subject,
                'body' => $message_template->body
            ),
            array(
                'id' => $message_template->id
            ));
    }

    function exists($name, $exclude_message_template_id)
    {
        $db_results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT id FROM message_template WHERE name = %s AND id != %d',
                $name,
                $exclude_message_template_id),
            OBJECT);
        return $db_results !== false && count($db_results) > 0;
    }

    function get_all_in_competition($competition_id)
    {
        return $this->get_objects(
            function ($row) {
                return self::to_message_template($row);
            },
            'SELECT * FROM message_template WHERE competition_id = %d',
            $competition_id);
    }

    function get($id)
    {
        return $this->get_object(
            function ($row) {
                return self::to_message_template($row);
            },
            'SELECT * FROM message_template WHERE id = %d',
            $id);
    }

    private static function to_message_template($result): MessageTemplate
    {
        $mt = new MessageTemplate();
        $mt->id = $result->id;
        $mt->competition_id = $result->competition_id;
        $mt->name = $result->name;
        $mt->subject = $result->subject;
        $mt->body = $result->body;
        return $mt;
    }

}