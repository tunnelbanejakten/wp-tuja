<?php

namespace tuja\data\store;

use tuja\data\model\ValidationException;
use tuja\data\model\Group;
use tuja\util\Database;

class GroupDao extends AbstractDao
{
    function __construct()
    {
		parent::__construct();
		$this->table = Database::get_table('team');
    }

    function create(Group $group)
    {
        $group->validate();

        if ($this->exists($group)) {
            throw new ValidationException('name', 'Det finns redan ett lag med detta namn.');
        }

        $affected_rows = $this->wpdb->insert($this->table,
            array(
                'random_id' => $this->id->random_string(),
                'competition_id' => $group->competition_id,
                'name' => $group->name,
                'type' => '',
                'category_id' => $group->category_id
            ),
            array(
                '%s',
                '%d',
                '%s',
                '%s',
                '%d'
            ));
        $success = $affected_rows !== false && $affected_rows === 1;

        return $success ? $this->wpdb->insert_id : false;
    }

    function update(Group $group)
    {
        $group->validate();

        if ($this->exists($group)) {
            throw new ValidationException('name', 'Det finns redan ett lag med detta namn.');
        }

        return $this->wpdb->update($this->table,
            array(
                'name' => $group->name,
                'category_id' => $group->category_id
            ),
            array(
                'id' => $group->id
            ));
    }

    function exists($group)
    {
        $db_results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT id FROM ' . $this->table . ' WHERE name = %s AND id != %d AND competition_id = %d',
                $group->name,
				$group->id,
				$group->competition_id
			),
			OBJECT
		);
        return $db_results !== false && count($db_results) > 0;
    }

    function get($id)
    {
        return $this->get_object(
	        'tuja\data\store\AbstractDao::to_group',
            'SELECT * FROM ' . $this->table . ' WHERE id = %d',
            $id);
    }

    function get_by_key($key)
    {
        return $this->get_object(
	        'tuja\data\store\AbstractDao::to_group',
            'SELECT * FROM ' . $this->table . ' WHERE random_id = %s',
            $key);
    }

    function get_all_in_competition($competition_id)
    {
        return $this->get_objects(
	        'tuja\data\store\AbstractDao::to_group',
            'SELECT * FROM ' . $this->table . ' WHERE competition_id = %d',
            $competition_id);
    }

}