<?php

namespace tuja\data\model;


class Form
{
    public $id;
    public $random_id;
    public $name;
    public $competition_id;
    public $allow_multiple_responses_per_group;
    public $submit_response_start;
    public $submit_response_end;

    public function validate()
    {
        if (strlen(trim($this->name)) < 1) {
            throw new ValidationException('name', 'Namnet måste fyllas i.');
        }
        if ($this->submit_response_start !== null && $this->submit_response_end !== null && $this->submit_response_start->diff($this->submit_response_end)->invert == 1) {
            throw new ValidationException('submit_response_end', 'Perioden måste sluta efter att den börjar.');
        }
    }
}