<?php

namespace data\store;

use tuja\data\model\Group;

class GroupDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function create(Group $group)
    {
        $affected_rows = $this->wpdb->insert('team',
            array(
                'random_id' => $this->id->random_string(10),
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