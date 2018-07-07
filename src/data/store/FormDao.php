<?php

namespace data\store;

class FormDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function get($id)
    {
        return $this->get_object(
            'data\store\AbstractDao::to_form',
            'SELECT * FROM form WHERE id = %d',
            $id);
    }

    function get_all_in_competition($competition_id)
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_form',
            'SELECT * FROM form WHERE competition_id = %d',
            $competition_id);
    }

}