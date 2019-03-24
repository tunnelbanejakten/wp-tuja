<?php

namespace tuja\data\model;


class MessageTemplate
{
    public $id;
    public $competition_id;
    public $name;
    public $subject;
    public $body;

    public function validate()
    {
	    if (strlen($this->name) > 100) {
		    throw new ValidationException('name', 'Namnet för långt.');
	    }
	    if (strlen($this->subject) > 65000) {
		    throw new ValidationException('subject', 'Ämnesraden för lång.');
	    }
	    if (strlen($this->body) > 65000) {
		    throw new ValidationException('body', 'Meddelandet för långt.');
	    }

    }
}