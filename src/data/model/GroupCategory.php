<?php

namespace tuja\data\model;


class GroupCategory
{
    public $id;
    public $competition_id;
    public $is_crew;
    public $name;

	public function validate()
	{
		if (strlen(trim($this->name)) < 1) {
			throw new ValidationException('name', 'Namnet måste fyllas i.');
		}
		if (strlen($this->name) > 20) {
			throw new ValidationException('name', 'Namnet får inte vara längre än 20 bokstäver.');
		}
	}
}