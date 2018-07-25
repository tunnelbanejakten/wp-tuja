<?php

namespace data\store;

use tuja\data\model\Competition;

class CompetitionDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function create(Competition $competition)
    {
        $query_template = '
            INSERT INTO competition (
                random_id,
                name
            ) VALUES (
                %S,
                %S
            )';
        return $this->wpdb->query($this->wpdb->prepare($query_template,
            $this->id->random_string(),
            $competition->name));
    }

    function get($id)
    {
        return $this->get_object(
            'data\store\AbstractDao::to_competition',
            'SELECT * FROM competition WHERE id = %d',
            $id);
    }

    function get_all()
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_competition',
            'SELECT * FROM competition');
    }

}