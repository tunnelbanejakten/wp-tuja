<?php

namespace tuja\data\store;

class PointsDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    // TODO: Handle problems in case replace(...) or delete(...) fail.
    function set($group_id, $question_id, $points = null)
    {
        if (isset($points)) {
            $this->wpdb->replace('form_question_points',
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
            $this->wpdb->delete('form_question_points',
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
            'data\store\AbstractDao::to_points',
            'SELECT * FROM form_question_points WHERE team_id = %d',
            $group_id);
    }

    function get_by_competition($competition_id)
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_points',
            '' .
            'SELECT p.* ' .
            'FROM form_question_points p ' .
            '  INNER JOIN form_question q ON p.form_question_id = q.id ' .
            '  INNER JOIN form f ON q.form_id = f.id ' .
            'WHERE f.competition_id = %d',
            $competition_id);
    }

}