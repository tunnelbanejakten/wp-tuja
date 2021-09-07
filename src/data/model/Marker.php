<?php


namespace tuja\data\model;

class Marker {
	const MARKER_TYPE_TASK  = 'TASK';
	const MARKER_TYPE_START = 'START';

	public $id;
	public $random_id;
	public $map_id;
	public $gps_coord_lat;
	public $gps_coord_long;
	public $type;
	public $name;
	public $description;
	public $link_form_id;
	public $link_form_question_id;
	public $link_question_group_id;
	public $link_station_id;

	public function validate() {
		if ( strlen( trim( $this->name ) ) < 1 ) {
			throw new ValidationException( 'name', 'Namnet måste fyllas i.' );
		}
		if ( strlen( $this->name ) > 100 ) {
			throw new ValidationException( 'name', 'Namnet får inte vara längre än 100 bokstäver.' );
		}
		if ( strlen( $this->type ) > 100 ) {
			throw new ValidationException( 'type', 'Typen får inte vara längre än 100 bokstäver.' );
		}
	}
}
