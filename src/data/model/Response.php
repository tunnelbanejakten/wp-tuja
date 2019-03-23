<?php

namespace tuja\data\model;


class Response
{
    public $id;
    public $form_question_id;
    public $group_id;
    public $answers;
    public $created;
    public $is_reviewed;

    public function __construct($answers = null)
    {
        $this->answers = $answers;
    }

	public function validate()
    {
	    if ( strlen( json_encode( $this->answers ) > 65000 ) ) {
	        throw new ValidationException( 'Du har svarat lite för mycket.' );
        }
    }
}