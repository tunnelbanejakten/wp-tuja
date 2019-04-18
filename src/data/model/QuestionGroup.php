<?php

namespace tuja\data\model;


class QuestionGroup {
	public $id;
	public $random_id;
	public $form_id;
	public $text;
	public $sort_order;
	public $score_max;

	public function validate() {
		if ( strlen( $this->text ) > 65000 ) {
			throw new ValidationException( 'text', 'Frågegruppens text är för lång.' );
		}
		if ( isset( $this->score_max ) && $this->score_max < 0 ) {
			throw new ValidationException( 'score_max', 'Maximal poäng måste vara mer än 0.' );
		}
	}
}