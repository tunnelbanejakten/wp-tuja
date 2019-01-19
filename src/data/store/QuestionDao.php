<?php

namespace tuja\data\store;

use tuja\data\model\Question;
use tuja\util\DB;

class QuestionDao extends AbstractDao
{
    function __construct($wpdb)
    {
        parent::__construct($wpdb);
    }

    function create(Question $question)
    {
		$question->validate();
		$table = DB::get_table('form_question');

        $query_template = '
            INSERT INTO ' . $table . ' (
                form_id,
                type,
                answer,
                text,
                sort_order,
                text_hint
            ) VALUES (
                %d,
                %s,
                %s,
                %s,
                %d,
                %s 
            )';
        return $this->wpdb->query($this->wpdb->prepare($query_template,
            $question->form_id,
            $question->type,
            json_encode(array(
                'validation' => 'one_of',
                'values' => $question->correct_answers,
                'options' => $question->possible_answers
            )),
            $question->text,
            $question->sort_order,
            $question->text_hint));
    }

    function delete($id)
    {
		$table = DB::get_table('form_question_points');
        $query_template = 'DELETE FROM ' . $table . ' WHERE id = %d';
        return $this->wpdb->query($this->wpdb->prepare($query_template, $id));
    }

    function update(Question $question)
    {
		$question->validate();
		$table = DB::get_table('form_question_points');

        $query_template = '
            UPDATE ' . $table . ' SET
                type = %s,
                answer = %s,
                text = %s,
                sort_order = %d,
                text_hint = %s
                WHERE 
                id = %d AND
                form_id = %d';
        return $this->wpdb->query($this->wpdb->prepare($query_template,
            $question->type,
            json_encode(array(
                'score_type' => $question->score_type,
                'score_max' => floatval($question->score_max),
                'values' => $question->correct_answers,
                'options' => $question->possible_answers
            )),
            $question->text,
            $question->sort_order,
            $question->text_hint,
            $question->id,
            $question->form_id));
    }

    function get_all_in_form($form_id)
    {
		$table = DB::get_table('form_question_points');

        return $this->get_objects(
            'data\store\AbstractDao::to_form_question',
            '
                SELECT * 
                FROM ' . $table . ' 
                WHERE form_id = %d 
                ORDER BY sort_order, id',
            $form_id);
    }

    function get_all_in_competition($competition_id)
    {
		$table = DB::get_table('form_question_points');

        return $this->get_objects(
            'data\store\AbstractDao::to_form_question',
            '
                SELECT q.* 
                FROM ' . $table . ' AS q INNER JOIN form AS f ON q.form_id = f.id
                WHERE f.competition_id = %d
                ORDER BY q.sort_order, q.id',
            $competition_id);
    }

}