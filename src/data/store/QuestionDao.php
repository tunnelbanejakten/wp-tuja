<?php

namespace tuja\data\store;

use tuja\data\model\Question;
use tuja\data\model\ValidationException;
use tuja\util\Database;

class QuestionDao extends AbstractDao
{
    function __construct()
    {
		parent::__construct();
		$this->table = Database::get_table('form_question');
    }

    function create(Question $question)
    {
		$question->validate();
	    if ( strlen( self::get_answer_config( $question ) ) > 65000 ) {
		    throw new ValidationException( 'answer', 'För mycket konfiguration för frågan.' );
	    }

        $query_template = '
            INSERT INTO ' . $this->table . ' (
                random_id,
                question_group_id,
                type,
                answer,
                text,
                sort_order,
                text_hint
            ) VALUES (
                %s,
                %d,
                %s,
                %s,
                %s,
                %d,
                %s 
			)';
		$query = $this->wpdb->prepare($query_template,
			$this->id->random_string(),
			$question->question_group_id,
			$question->type,
			self::get_answer_config( $question ),
			$question->text,
			$question->sort_order,
			$question->text_hint
		);
		
        return $this->wpdb->query($query);
    }

    function delete($id)
    {
        $query_template = 'DELETE FROM ' . $this->table . ' WHERE id = %d';
        return $this->wpdb->query($this->wpdb->prepare($query_template, $id));
    }

    function update(Question $question)
    {
		$question->validate();

        $query_template = '
            UPDATE ' . $this->table . ' SET
                type = %s,
                answer = %s,
                text = %s,
                sort_order = %d,
                text_hint = %s
                WHERE 
                id = %d';
        return $this->wpdb->query($this->wpdb->prepare($query_template,
            $question->type,
	        self::get_answer_config( $question ),
            $question->text,
            $question->sort_order,
            $question->text_hint,
            $question->id));
	}
	
	public function get($question_id) {
		$objects = $this->get_objects(
			function ( $row ) {
				return self::to_form_question( $row );
			},
			'
				SELECT q.* 
                FROM ' . $this->table . ' AS q
                WHERE q.id = %d
				ORDER BY q.sort_order, q.id
			',
			$question_id
		);	
		return reset($objects);
	}

    function get_all_in_form($form_id)
    {
		return $this->get_objects(
			function ( $row ) {
				return self::to_form_question( $row );
			},
			'
                SELECT q.* 
                FROM ' . $this->table . ' AS q 
                    INNER JOIN ' . Database::get_table( 'form_question_group' ) . ' AS grp ON q.question_group_id = grp.id
                WHERE grp.form_id = %d
                ORDER BY grp.sort_order, q.sort_order, q.id',
            $form_id);
    }

    function get_all_in_group($group_id)
    {
		return $this->get_objects(
			function ( $row ) {
				return self::to_form_question( $row );
			},
			'
                SELECT q.* 
                FROM ' . $this->table . ' AS q 
				WHERE q.question_group_id = %d
                ORDER BY q.sort_order, q.id',
            $group_id);
    }

    function get_all_in_competition($competition_id)
    {
		return $this->get_objects(
			function ( $row ) {
				return self::to_form_question( $row );
			},
			'
                SELECT q.* 
                FROM ' . $this->table . ' AS q 
                    INNER JOIN ' . Database::get_table( 'form_question_group' ) . ' AS grp ON q.question_group_id = grp.id
                    INNER JOIN ' . Database::get_table( 'form' ) . ' AS f ON grp.form_id = f.id
                WHERE f.competition_id = %d
                ORDER BY grp.sort_order, q.sort_order, q.id',
            $competition_id);
    }

	protected static function to_form_question( $result ): Question {
		$q                    = new Question();
		$q->id                = $result->id;
		$q->question_group_id = $result->question_group_id;
		$q->type              = $result->type;
		self::set_answer_config( $q, $result->answer );
		$q->text             = $result->text;
		$q->sort_order       = $result->sort_order;
		$q->text_hint        = $result->text_hint;

		return $q;
	}

	private static function set_answer_config( Question $question, $json_string ) {
		$conf                       = json_decode( $json_string, true );
		$question->possible_answers = $conf['options'];
		$question->correct_answers  = $conf['values'];
		$question->score_type       = $conf['score_type'];
		$question->score_max        = $conf['score_max'];
	}

	private static function get_answer_config( Question $question ) {
		return json_encode( array(
			'score_type' => $question->score_type,
			'score_max'  => floatval( $question->score_max ),
			'values'     => $question->correct_answers,
			'options'    => $question->possible_answers
		) );
	}
}