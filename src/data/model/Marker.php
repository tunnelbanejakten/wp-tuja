<?php


namespace tuja\data\model;

class Map {
	private $id;
	private $random_id;
	private $map_id;
	private $gps_coord_lat;
	private $gps_coord_long;
	private $type;
	private $name;
	private $description;
	private $link_form_id;
	private $link_form_question_id;
	private $link_question_group_id;
	private $link_station_id;

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
