<?php

namespace tuja\data\model;


class Points {

	// One, and only one, of these three should be set:
	public $form_question_id;
	public $station_id;
	public $name;

	public $group_id;
	public $points;
	public $created;

	public function validate() {
	}
}
