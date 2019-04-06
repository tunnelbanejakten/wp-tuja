<?php

namespace tuja\data\model;


class QuestionGroup {
	public $id;
	public $random_id;
	public $form_id;
	public $text;
	public $sort_order;

	public function validate() {
		if ( strlen( $this->text ) > 65000 ) {
			throw new ValidationException( 'text', 'Frågegruppens text är för lång.' );
		}
	}
}