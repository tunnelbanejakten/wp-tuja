<?php


namespace tuja\data\model;

class DuelGroup {
	public $id;
	public $competition_id;
	public $name;
	public $link_form_question_id;
	public $created_at;
	public function validate() {
		if ( strlen( trim( $this->name ) ) < 1 ) {
			throw new ValidationException( 'name', 'Namnet måste fyllas i.' );
		}
		if ( strlen( $this->name ) > 100 ) {
			throw new ValidationException( 'name', 'Namnet får inte vara längre än 100 bokstäver.' );
		}
	}
}
