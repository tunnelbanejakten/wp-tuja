<?php

namespace tuja\data\model;


class Competition
{
    public $id;
    public $random_id;
    public $name;
    public $create_group_start;
    public $create_group_end;
    public $edit_group_start;
    public $edit_group_end;
    public $event_start;
    public $event_end;

	public $initial_group_status;
    public function validate()
    {
        if (strlen(trim($this->name)) < 1) {
            throw new ValidationException('name', 'Namnet måste fyllas i.');
        }
        if (strlen($this->name) > 100) {
            throw new ValidationException('name', 'Namnet är för långt.');
        }
        if ($this->create_group_start !== null && $this->create_group_end !== null && $this->create_group_start->diff($this->create_group_end)->invert == 1) {
            throw new ValidationException('create_group_end', 'Perioden för att anmäla måste sluta efter att den börjar.');
        }
        if ($this->edit_group_start !== null && $this->edit_group_end !== null && $this->edit_group_start->diff($this->edit_group_end)->invert == 1) {
            throw new ValidationException('edit_group_end', 'Perioden för att ändra anmälan måste sluta efter att den börjar.');
        }
        if ($this->event_start !== null && $this->event_end !== null && $this->event_start->diff($this->event_end)->invert == 1) {
            throw new ValidationException('edit_group_end', 'Tävlingen måste sluta efter att den börjar.');
        }
	    if ( $this->initial_group_status !== null && ! in_array( $this->initial_group_status, self::allowed_initial_statuses() ) ) {
		    throw new ValidationException( 'initial_group_status', 'Nya grupper kan bara ha en av följande statusar: ' . join( ', ', self::allowed_initial_statuses() ) );
	    }
    }

	public static function allowed_initial_statuses() {
		return array_merge( [ Group::DEFAULT_STATUS ], Group::STATUS_TRANSITIONS[ Group::DEFAULT_STATUS ] );
	}

}