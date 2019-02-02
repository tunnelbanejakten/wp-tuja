<?php

namespace tuja\data\store;

use tuja\util\DB;

class PointsDao extends AbstractDao
{
    function __construct()
    {
        parent::__construct();
		$this->table = DB::get_table('form_question_points');
    }

    // TODO: Handle problems in case replace(...) or delete(...) fail.
    function set($group_id, $question_id, $points = null)
    {
        if (isset($points)) {
            $this->wpdb->replace($this->table,
                array(
                    'form_question_id' => $question_id,
                    'team_id' => $group_id,
                    'points' => $points
                ),
                array(
                    '%d',
                    '%d',
                    '%d'
                ));
        } else {
            $this->wpdb->delete($this->table,
                array(
                    'form_question_id' => $question_id,
                    'team_id' => $group_id
                ),
                array(
                    '%d',
                    '%d'
                ));
        }
    }

    function get_by_group($group_id)
    {
		return $this->get_objects(
			'tuja\data\store\AbstractDao::to_points',
            'SELECT * FROM ' . $this->table . ' WHERE team_id = %d',
            $group_id);
    }

    function get_by_competition($competition_id)
    {
        return $this->get_objects(
	        'tuja\data\store\AbstractDao::to_points',
            '' .
            'SELECT p.* ' .
            'FROM ' . $this->table . ' p ' .
            '  INNER JOIN ' . DB::get_table( 'form_question' ) . ' q ON p.form_question_id = q.id ' .
            '  INNER JOIN ' . DB::get_table( 'form' ) . ' f ON q.form_id = f.id ' .
            'WHERE f.competition_id = %d',
            $competition_id);
    }
}