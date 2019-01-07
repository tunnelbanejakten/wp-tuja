<?php

namespace tuja\data\model;

use data\model\ValidationException;

class Person
{
    public $id;
    public $random_id;
    public $name;
    public $group_id;
    public $phone;
    public $phone_verified;
    public $email;
    public $email_verified;

    public function validate()
    {
        if (empty(trim($this->name))) {
            throw new ValidationException('name', 'Namnet måste fyllas i.');
        }
        if (strlen($this->name) > 100) {
            throw new ValidationException('name', 'Namnet får inte vara längre än 100 bokstäver.');
        }
        if (strlen($this->email) > 50) {
            throw new ValidationException('email', 'E-postadress får bara vara 50 tecken lång.');
        }
        if (!empty(trim($this->email)) && preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i', $this->email) !== 1) {
            throw new ValidationException('email', 'E-postadressen ser konstig ut.');
        }
        if (strlen($this->phone) > 50) {
            throw new ValidationException('phone', 'Telefonnumret får bara vara 50 tecken långt.');
        }
        if (!empty(trim($this->phone)) && preg_match('/^\+?[0-9 -]{6,}$/', $this->phone) !== 1) {
            throw new ValidationException('phone', 'Telefonnummer ser konstigt ut');
        }
    }

    public static function from_email(string $email)
    {
        $person = new Person();
        $person->name = substr($email, 0, strpos($email, '@'));
        $person->email = $email;
        return $person;
    }

}