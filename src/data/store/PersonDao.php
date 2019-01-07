<?php

namespace tuja\data\store;

use tuja\data\model\Person;

class PersonDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function create(Person $person)
    {
        $person->validate();

        $affected_rows = $this->wpdb->insert('person',
            array(
                'random_id' => $this->id->random_string(),
                'name' => $person->name,
                'team_id' => $person->group_id,
                'phone' => $person->phone,
                'email' => $person->email
            ),
            array(
                '%s',
                '%s',
                '%d',
                '%s',
                '%s'
            ));

        $success = $affected_rows !== false && $affected_rows === 1;

        return $success ? $this->wpdb->insert_id : false;
    }

    function update(Person $person)
    {
        $person->validate();

        return $this->wpdb->update('person',
            array(
                'name' => $person->name,
                'email' => $person->email,
                'phone' => $person->phone
            ),
            array(
                'id' => $person->id
            ));
    }

    function get($id)
    {
        return $this->get_object(
            'data\store\AbstractDao::to_person',
            'SELECT * FROM person WHERE id = %d',
            $id);
    }

    function get_by_key($key)
    {
        return $this->get_object(
            'data\store\AbstractDao::to_person',
            'SELECT * FROM person WHERE random_id = %s',
            $key);
    }


    function get_all_in_group($group_id)
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_person',
            'SELECT * FROM person WHERE team_id = %d',
            $group_id);
    }

    function get_all_in_competition($competition_id)
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_person',
            'SELECT p.* '.
            'FROM person AS p INNER JOIN team AS t ON p.team_id = t.id '.
            'WHERE t.competition_id = %d',
            $competition_id);
    }

    public function delete_by_key($key)
    {
        $affected_rows = $this->wpdb->delete('person', array(
            'random_id' => $key
        ));
        return $affected_rows !== false && $affected_rows === 1;
    }

}