<?php

namespace data\model;

use Exception;

class ValidationException extends Exception
{
    private $field;

    public function __construct($field, $message)
    {
        parent::__construct($message);
        $this->field = $field;
    }

    public function getField()
    {
        return $this->field;
    }
}