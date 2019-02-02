<?php

namespace tuja\data\store;

use tuja\data\model\Person;
use tuja\util\DB;

class PersonDao extends AbstractDao
{
    function __construct()
    {
		parent::__construct();
		$this->table = DB::get_table('person');
    }

    function create(Person $person)
    {
        $person->validate();

        $affected_rows = $this->wpdb->insert($this->table,
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

        return $this->wpdb->update($this->table,
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
	        'tuja\data\store\AbstractDao::to_person',
            'SELECT * FROM ' . $this->table . ' WHERE id = %d',
            $id);
    }

    function get_by_key($key)
    {
        return $this->get_object(
	        'tuja\data\store\AbstractDao::to_person',
            'SELECT * FROM ' . $this->table . ' WHERE random_id = %s',
            $key);
    }


    function get_all_in_group($group_id)
    {
        return $this->get_objects(
	        'tuja\data\store\AbstractDao::to_person',
            'SELECT * FROM ' . $this->table . ' WHERE team_id = %d',
            $group_id);
    }

    function get_all_in_competition($competition_id)
    {
        return $this->get_objects(
	        'tuja\data\store\AbstractDao::to_person',
	        'SELECT p.* ' .
	        'FROM ' . $this->table . ' AS p INNER JOIN ' . DB::get_table( 'team' ) . ' AS t ON p.team_id = t.id ' .
	        'WHERE t.competition_id = %d',
            $competition_id);
    }

    public function delete_by_key($key)
    {
        $affected_rows = $this->wpdb->delete($this->table, array(
            'random_id' => $key
        ));
        return $affected_rows !== false && $affected_rows === 1;
    }

}