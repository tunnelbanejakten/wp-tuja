<?php

namespace tuja\data\model;


class Group
{
    public $id;
    public $random_id;
    public $competition_id;
    public $name;
    public $category_id;
	public $age_competing_avg;
	public $age_competing_stddev;
	public $age_competing_min;
	public $age_competing_max;
	public $count_competing;
	public $count_follower;
	public $count_team_contact;

    public function validate()
    {
        if (strlen(trim($this->name)) < 1) {
            throw new ValidationException('name', 'Namnet måste fyllas i.');
        }
        if (strlen($this->name) > 100) {
            throw new ValidationException('name', 'Namnet får inte vara längre än 100 bokstäver.');
        }
    }
}