<?php


namespace tuja\data\model;

class Event {

	const EVENT_VIEW           = 'VIEW';
	const OBJECT_TYPE_QUESTION = 'QUESTION';

	public $id;
	public $competition_id;
	public $created_at;
	public $event_name;
	public $event_data;
	public $group_id;
	public $person_id;
	public $object_type;
	public $object_id;

	public function validate() {
		if ( strlen( trim( $this->event_name ) ) < 1 ) {
			throw new ValidationException( 'event_name', 'Namnet måste fyllas i.' );
		}
		if ( strlen( $this->event_name ) > 50 ) {
			throw new ValidationException( 'event_name', 'Namnet får inte vara längre än 50 bokstäver.' );
		}
	}
}
