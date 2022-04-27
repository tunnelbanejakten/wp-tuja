<?php

namespace tuja\data\model;


class Response
{
    public $id;
    public $form_question_id;
    public $group_id;
    public $submitted_answer;
    public $created;
    public $is_reviewed;
    public $view_event_time_elapsed; // Seconds since the first registred "view event" for this group for this question.

    public function __construct($submitted_answer = null)
    {
        $this->submitted_answer = $submitted_answer;
    }

	public function validate()
    {
	    if ( strlen( json_encode( $this->submitted_answer ) > 65000 ) ) {
	        throw new ValidationException( 'Du har svarat lite för mycket.' );
        }
    }
}