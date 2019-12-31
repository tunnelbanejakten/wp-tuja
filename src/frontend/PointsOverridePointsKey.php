<?php


namespace tuja\frontend;


class PointsOverridePointsKey {
	public $group_id;
	public $question_id;

	public function __construct( $group_id, $question_id ) {
		$this->group_id    = $group_id;
		$this->question_id = $question_id;
	}
}