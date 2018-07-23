<?php

namespace data\store;

use tuja\data\model\Person;

class PersonDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function create(Person $person)
    {
        return $this->wpdb->insert('person',
            array(
                'random_id' => $this->id->random_string(10),
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
    }

    function update(Person $person)
    {
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

    function get_all_in_group($group_id)
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_person',
            'SELECT * FROM person WHERE team_id = %d',
            $group_id);
    }

    public function delete_by_key($key)
    {
        $affected_rows = $this->wpdb->delete('person', array(
            'random_id' => $key
        ));
        return $affected_rows !== false && $affected_rows === 1;
    }

}