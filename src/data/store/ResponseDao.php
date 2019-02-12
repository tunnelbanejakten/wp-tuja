<?php

namespace tuja\data\store;

use tuja\data\model\Response;
use tuja\util\Database;

class ResponseDao extends AbstractDao
{
    function __construct()
    {
		parent::__construct();
		$this->table = Database::get_table('form_question_response');
    }

    function create(Response $response)
    {
		$response->validate();

        $query_template = '
            INSERT INTO ' . $this->table . ' (
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
			'tuja\data\store\AbstractDao::to_response',
            'SELECT * FROM ' . $this->table . ' WHERE team_id = %d ORDER BY id',
            $group_id);
    }

    function get_latest_by_group($group_id)
    {
        $latest_responses = [];
        $all_responses = $this->get_by_group($group_id);
        foreach ($all_responses as $response) {
            $latest_responses[$response->form_question_id] = $response;
        }
        return $latest_responses;
    }

    function get_not_reviewed($competition_id)
    {
		$all_responses = $this->get_objects(
			'tuja\data\store\AbstractDao::to_response',
            'SELECT r.* ' .
            'FROM ' . $this->table . ' r ' .
            'INNER JOIN ' . Database::get_table( 'form_question' ) . ' fq ON r.form_question_id = fq.id ' .
            'INNER JOIN ' . Database::get_table( 'form' ) . ' f ON (fq.form_id = f.id AND f.competition_id = %d) ' .
            'ORDER BY r.id',
            $competition_id);

        $latest_responses = [];
        foreach ($all_responses as $response) {
            $latest_responses[$response->form_question_id . '__' . $response->group_id] = $response;
        }
        return array_filter($latest_responses, function ($response) {
            return !$response->is_reviewed;
        });
    }

    function mark_as_reviewed(array $response_ids)
    {
        $ids = join(', ', array_map('intval', array_filter($response_ids, 'is_numeric')));
        $query = sprintf('UPDATE ' . $this->table . ' SET is_reviewed = TRUE WHERE id IN (%s)', $ids);
        $affected_rows = $this->wpdb->query($query);
        return $affected_rows === count($ids);
    }
}