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
        $form->validate();

        $affected_rows = $this->wpdb->insert('form',
            array(
                'competition_id' => $form->competition_id,
                'name' => $form->name,
                'allow_multiple_responses_per_team' => 1,
                'submit_response_start' => self::to_db_date($form->submit_response_start),
                'submit_response_end' => self::to_db_date($form->submit_response_end)
            ),
            array(
                '%d',
                '%s',
                '%d',
                '%d',
                '%d'
            ));
        $success = $affected_rows !== false && $affected_rows === 1;

        return $success ? $this->wpdb->insert_id : false;
    }

    function update(Form $form)
    {
        $form->validate();

        return $this->wpdb->update('form',
            array(
                'name' => $form->name,
                'submit_response_start' => self::to_db_date($form->submit_response_start),
                'submit_response_end' => self::to_db_date($form->submit_response_end)
            ),
            array(
                'id' => $form->id
            ));
    }

    function get($id): Form
    {
        return $this->get_object(
            function ($row) {
                return self::to_form($row);
            },
            'SELECT * FROM form WHERE id = %d',
            $id);
    }

    function get_all_in_competition($competition_id)
    {
        return $this->get_objects(
            function ($row) {
                return self::to_form($row);
            },
            'SELECT * FROM form WHERE competition_id = %d',
            $competition_id);
    }

    private static function to_form($result): Form
    {
        $f = new Form();
        $f->id = $result->id;
        $f->competition_id = $result->competition_id;
        $f->name = $result->name;
        $f->allow_multiple_responses_per_group = $result->allow_multiple_responses_per_team;
        $f->submit_response_start = self::from_db_date($result->submit_response_start);
        $f->submit_response_end = self::from_db_date($result->submit_response_end);
        return $f;
    }

}