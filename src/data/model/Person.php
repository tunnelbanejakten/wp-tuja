<?php

namespace tuja\data\model;

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
    public $food;
    public $is_competing;
    public $is_group_contact;
    public $pno;

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
        if (strlen($this->food) > 100) {
            throw new ValidationException('food', 'Högst 100 tecken.');
        }
        if (empty(trim($this->pno))) {
            throw new ValidationException('pno', 'Födelsedag och sånt måste fyllas i');
        }
        /*
        Valid values:
	        8311090123
			831109-0123
			198311090123
			19831109-0123
			831109
			83-11-09
			19831109
			1983-11-09
			198311090000
			8311090000
			1983-11-09--0123

        Invalid values:
			19831109-012
			19831109-01
			12345
			198300000000
			8300000000
			830000000000
			1234567890
			nej
        */
        if (preg_match('/^(19|20)?[0-9]{2}-?(0[1-9]|[1-2][0-9])-?[0-3][0-9](-*[0-9]{4})?$/', $this->pno) !== 1) {
            throw new ValidationException('pno', 'Födelsedag och sånt ser konstigt ut');
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