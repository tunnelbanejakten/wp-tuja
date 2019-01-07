<?php

namespace tuja\data\store;

use tuja\data\model\Competition;

class CompetitionDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function create(Competition $competition)
    {
        $affected_rows = $this->wpdb->insert('competition',
            array(
                'random_id' => $this->id->random_string(),
                'name' => $competition->name,
                'create_group_start' => self::to_db_date($competition->create_group_start),
                'create_group_end' => self::to_db_date($competition->create_group_end),
                'edit_group_start' => self::to_db_date($competition->edit_group_start),
                'edit_group_end' => self::to_db_date($competition->edit_group_end)
            ),
            array(
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%d'
            ));
        $success = $affected_rows !== false && $affected_rows === 1;

        return $success ? $this->wpdb->insert_id : false;
    }

    function update(Competition $competition)
    {
        $competition->validate();

        return $this->wpdb->update('competition',
            array(
                'name' => $competition->name,
                'create_group_start' => self::to_db_date($competition->create_group_start),
                'create_group_end' => self::to_db_date($competition->create_group_end),
                'edit_group_start' => self::to_db_date($competition->edit_group_start),
                'edit_group_end' => self::to_db_date($competition->edit_group_end)
            ),
            array(
                'id' => $competition->id
            ));
    }

    function get($id)
    {
        return $this->get_object(
            function ($row) {
                return self::to_competition($row);
            },
            'SELECT * FROM competition WHERE id = %d',
            $id);
    }

    function get_all()
    {
        return $this->get_objects(
            function ($row) {
                return self::to_competition($row);
            },
            'SELECT * FROM competition');
    }

    private static function to_competition($result): Competition
    {
        $c = new Competition();
        $c->name = $result->name;
        $c->id = $result->id;
        $c->random_id = $result->random_id;
        $c->create_group_start = self::from_db_date($result->create_group_start);
        $c->create_group_end = self::from_db_date($result->create_group_end);
        $c->edit_group_start = self::from_db_date($result->edit_group_start);
        $c->edit_group_end = self::from_db_date($result->edit_group_end);
        return $c;
    }

}