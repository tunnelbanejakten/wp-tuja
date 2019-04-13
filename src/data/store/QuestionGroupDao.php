<?php

namespace tuja\data\store;

use tuja\data\model\Question;
use tuja\data\model\QuestionGroup;
use tuja\util\Database;

class QuestionGroupDao extends AbstractDao {
	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'form_question_group' );
	}

	function create( QuestionGroup $group ) {
		$group->validate();

		$affected_rows = $this->wpdb->insert( $this->table,
			array(
				'random_id'  => $this->id->random_string(),
				'form_id'    => $group->form_id,
				'text'       => $group->text,
				'sort_order' => $group->sort_order
			),
			array(
				'%s',
				'%d',
				'%s',
				'%d'
			) );
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	function delete( $id ) {
		$query_template = 'DELETE FROM ' . $this->table . ' WHERE id = %d';

		return $this->wpdb->query( $this->wpdb->prepare( $query_template, $id ) );
	}

	function update( Question $group ) {
		$group->validate();

		return $this->wpdb->update( $this->table,
			array(
				'text'       => $group->text,
				'sort_order' => $group->sort_order
			),
			array(
				'id' => $group->id
			) );
	}

	function get($id)
    {
        return $this->get_object(
            function ($row) {
                return self::to_form($row);
            },
            'SELECT * FROM ' . $this->table . ' WHERE id = %d',
            $id);
    }

	function get_all_in_form( $form_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_question_group( $row );
			},
			'
                SELECT * 
                FROM ' . $this->table . ' 
                WHERE form_id = %d 
                ORDER BY sort_order, id',
			$form_id );
	}

	function get_all_in_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_question_group( $row );
			},
			'
                SELECT grp.* 
                FROM ' . $this->table . ' AS grp 
                    INNER JOIN ' . Database::get_table( 'form' ) . ' AS f ON grp.form_id = f.id
                WHERE f.competition_id = %d
                ORDER BY grp.sort_order, grp.id',
			$competition_id );
	}

	protected static function to_question_group( $result ): QuestionGroup {
		$q             = new QuestionGroup();
		$q->id         = $result->id;
		$q->form_id    = $result->form_id;
		$q->random_id  = $result->random_id;
		$q->text       = $result->text;
		$q->sort_order = $result->sort_order;

		return $q;
	}

}