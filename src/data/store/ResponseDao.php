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
        $query_template = '
            INSERT INTO form_question_response (
                form_question_id,
                team_id,
                answer,
                points
            ) VALUES (
                %s,
                %s,
                %s,
                %s
            )';
        return $this->wpdb->query($this->wpdb->prepare($query_template,
            $response->form_question_id,
            $response->team_id,
            $response->answer,
            $response->points));
    }

    function get_by_team($team_id)
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_response',
            'SELECT * FROM form_question_response WHERE team_id = %d',
            $team_id);
    }

}