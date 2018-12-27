<?php

namespace tuja\data\model;


use data\model\ValidationException;

class Competition
{
    public $id;
    public $random_id;
    public $name;
    public $create_group_start;
    public $create_group_end;
    public $edit_group_start;
    public $edit_group_end;

    public function validate()
    {
        if (strlen(trim($this->name)) < 1) {
            throw new ValidationException('name', 'Namnet måste fyllas i.');
        }
        if ($this->create_group_start !== null && $this->create_group_end !== null && $this->create_group_start->diff($this->create_group_end)->invert == 1) {
            throw new ValidationException('create_group_end', 'Perioden för att anmäla måste sluta efter att den börjar.');
        }
        if ($this->edit_group_start !== null && $this->edit_group_end !== null && $this->edit_group_start->diff($this->edit_group_end)->invert == 1) {
            throw new ValidationException('edit_group_end', 'Perioden för att ändra anmälan måste sluta efter att den börjar.');
        }
    }

}