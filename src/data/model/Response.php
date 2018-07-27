<?php

namespace tuja\data\model;


use Exception;

class Response
{
    public $id;
    public $form_question_id;
    public $group_id;
    public $answers;
    public $points;

    public function __construct($answers = null)
    {
        $this->answers = $answers;
    }


    public function validate()
    {
        if (strlen(json_encode($this->answers) > 500)) {
            throw new Exception('Du har svarat lite för mycket.');
        }
    }
}