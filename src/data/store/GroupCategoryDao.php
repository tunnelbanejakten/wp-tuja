<?php

namespace tuja\data\store;

use data\model\ValidationException;
use tuja\data\model\GroupCategory;
use tuja\util\DB;

class GroupCategoryDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function create(GroupCategory $category)
    {
        $category->validate();

        if ($this->exists($category->name, $category->id)) {
            throw new ValidationException('name', 'Det finns redan en kategori med detta namn.');
        }

        $affected_rows = $this->wpdb->insert(DB::get_table('team_category'),
            array(
                'competition_id' => $category->competition_id,
                'name' => $category->name,
                'is_crew' => $category->is_crew
            ),
            array(
                '%d',
                '%s',
                '%d'
            ));
        $success = $affected_rows !== false && $affected_rows === 1;

        return $success ? $this->wpdb->insert_id : false;
    }

    function update(GroupCategory $category)
    {
        $category->validate();

        if ($this->exists($category->name, $category->id)) {
            throw new ValidationException('name', 'Det finns redan en kategori med detta namn.');
        }

        return $this->wpdb->update('team_category',
            array(
                'name' => $category->name,
                'is_crew' => $category->is_crew
            ),
            array(
                'id' => $category->id
            ));
    }

    function exists($name, $exclude_group_id)
    {
        $db_results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT id FROM team_category WHERE name = %s AND id != %d',
                $name,
                $exclude_group_id),
            OBJECT);
        return $db_results !== false && count($db_results) > 0;
    }

    function get($id)
    {
        return $this->get_object(
            'data\store\AbstractDao::to_group_category',
            'SELECT * FROM team_category WHERE id = %d',
            $id);
    }

    function get_all_in_competition($competition_id)
    {
        return $this->get_objects(
            'data\store\AbstractDao::to_group_category',
            'SELECT * FROM team_category WHERE competition_id = %d ORDER BY is_crew, name',
            $competition_id);
    }

    function delete($id)
    {
        $query_template = 'DELETE FROM team_category WHERE id = %d';
        return $this->wpdb->query($this->wpdb->prepare($query_template, $id));
    }

}