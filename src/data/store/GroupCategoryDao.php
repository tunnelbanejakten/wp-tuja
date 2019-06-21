<?php

namespace tuja\data\store;

use tuja\data\model\ValidationException;
use tuja\data\model\GroupCategory;
use tuja\util\Database;

class GroupCategoryDao extends AbstractDao
{
    function __construct()
    {
		parent::__construct();
		$this->table = Database::get_table('team_category');
    }

    function create(GroupCategory $category)
    {
        $category->validate();

        if ($this->exists($category)) {
            throw new ValidationException('name', 'Det finns redan en kategori med detta namn.');
        }

        $affected_rows = $this->wpdb->insert($this->table,
            array(
	            'competition_id' => $category->competition_id,
	            'name'           => $category->name,
	            'is_crew'        => $category->is_crew,
	            'rule_set'       => $category->rule_set_class_name
            ),
            array(
                '%d',
                '%s',
	            '%d',
	            '%s'
            ));
        $success = $affected_rows !== false && $affected_rows === 1;

        return $success ? $this->wpdb->insert_id : false;
    }

    function update(GroupCategory $category)
    {
        $category->validate();

        if ($this->exists($category)) {
            throw new ValidationException('name', 'Det finns redan en kategori med detta namn.');
        }

        return $this->wpdb->update($this->table,
            array(
	            'name'     => $category->name,
	            'is_crew'  => $category->is_crew,
	            'rule_set' => $category->rule_set_class_name
            ),
            array(
                'id' => $category->id
            ));
    }

    function exists(GroupCategory $category)
    {
        $db_results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                'SELECT id FROM ' . $this->table . ' WHERE name = %s AND id != %d AND competition_id = %d',
	            $category->name,
	            $category->id,
	            $category->competition_id),
            OBJECT);
        return $db_results !== false && count($db_results) > 0;
    }

    function get($id)
    {
        return $this->get_object(
	        function ( $row ) {
		        return self::to_group_category( $row );
	        },
            'SELECT * FROM ' . $this->table . ' WHERE id = %d',
            $id);
    }

    function get_all_in_competition($competition_id)
    {
        return $this->get_objects(
	        function ( $row ) {
		        return self::to_group_category( $row );
	        },
	        'SELECT * FROM ' . $this->table . ' WHERE competition_id = %d ORDER BY is_crew, name',
            $competition_id);
    }

    function delete($id)
    {
        $query_template = 'DELETE FROM ' . $this->table . ' WHERE id = %d';
        return $this->wpdb->query($this->wpdb->prepare($query_template, $id));
    }

	private static function to_group_category( $result ): GroupCategory {
		$gc                      = new GroupCategory();
		$gc->id                  = $result->id;
		$gc->competition_id      = $result->competition_id;
		$gc->is_crew             = $result->is_crew != 0;
		$gc->name                = $result->name;
		$gc->rule_set_class_name = $result->rule_set;

		return $gc;
	}
}