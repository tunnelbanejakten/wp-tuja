<?php

namespace data\store;

use data\model\ValidationException;
use tuja\data\model\Group;

class GroupDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function create(Group $group)
    {
        $group->validate();

        if ($this->exists($group->name, $group->id)) {
            throw new ValidationException('name', 'Det finns redan ett lag med detta namn.');
        }

        $affected_rows = $this->wpdb->insert('team',
            array(
                'random_id' => $this->id->random_string(),
                'competition_id' => $group->competition_id,
                'name' => $group->name,
                'type' => $group->type
            ),
            array(
                '%s',
                '%d',
                '%s',
                '%s'
            ));
        $success = $affected_rows !== false && $affected_rows === 1;

        return $success ? $this->wpdb->insert_id : false;
    }

    function update(Group $group)
    {
        $group->validate();

        if ($this->exists($group->name, $group->id)) {
            throw new ValidationException('name', 'Det finns redan ett lag med detta namn.');
        }

        return $this->wpdb->update('team',
            array(
                'name' => $group->name
            ),
            array(
                'id' => $group->id
            ));
    }

    function exists($name, $exclude_group_id)
    {
        $db_results = $this->wpdb->get_results($this->wpdb->prepare('SELECT id FROM team WHERE name = %s AND id != %d', $name, $exclude_group_id), OBJECT);
        return $db_results !== false && count($db_results) > 0;
    }

    function get($id)
    {
        return $this->get_object(
            'data\store\AbstractDao::to_group',
            'SELECT * FROM team WHERE id = %d',
            $id);
    }

    function get_by_key($key)
    {
        return $this->get_object(
            'data\store\AbstractDao::to_group',
            'SELECT * FROM team WHERE random_id = %s',
            $key);
    }

    function get_all_in_competition($competition_id)
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_group',
            'SELECT * FROM team WHERE competition_id = %d',
            $competition_id);
    }

}