<?php

namespace data\store;

use tuja\data\model\Response;

class ResponseDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function create(Response $response)
    {
        $response->validate();

        $query_template = '
            INSERT INTO form_question_response (
                form_question_id,
                team_id,
                answer
            ) VALUES (
                %d,
                %d,
                %s
            )';
        return $this->wpdb->query($this->wpdb->prepare($query_template,
            $response->form_question_id,
            $response->group_id,
            json_encode($response->answers)));
    }

    function get_by_group($group_id)
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_response',
            'SELECT * FROM form_question_response WHERE team_id = %d',
            $group_id);
    }

}