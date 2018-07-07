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
        $query_template = '
            INSERT INTO team (
                random_id,
                competition_id,
                name,
                type
            ) VALUES (
                %s,
                %s,
                %s,
                %s
            )';
        return $this->wpdb->query($this->wpdb->prepare($query_template,
            $this->id->random_string(10),
            $group->competition_id,
            $group->name,
            $group->type));
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