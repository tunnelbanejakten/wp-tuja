<?php

namespace tuja\data\model;


use Exception;

class Response
{
    public $id;
    public $form_question_id;
    public $group_id;
    public $answer;
    public $points;

    public function validate()
    {
        if (strlen($this->answer) > 500) {
            throw new Exception('Du får inte skriva in fler än 500 tecken.');
        }
    }
}