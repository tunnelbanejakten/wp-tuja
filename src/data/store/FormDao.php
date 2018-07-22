<?php

namespace data\store;

use tuja\data\model\Form;

class FormDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function create(Form $form)
    {
        $query_template = '
            INSERT INTO form (
                competition_id,
                name,
                allow_multiple_responses_per_team,
                accept_responses_from,
                accept_responses_until
            ) VALUES (
                %s,
                %s,
                1,
                NULL,
                NULL 
            )';
        return $this->wpdb->query($this->wpdb->prepare($query_template,
            $form->competition_id,
            $form->name));
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