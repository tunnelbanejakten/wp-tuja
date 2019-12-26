<?php


namespace tuja\data\model;


class Station {
	public $id;
	public $random_id;
	public $competition_id;
	public $name;
	public $location_gps_coord_lat;
	public $location_gps_coord_long;
	public $location_description;

	public function validate() {
		if ( strlen( trim( $this->name ) ) < 1 ) {
			throw new ValidationException('name', 'Namnet måste fyllas i.');
		}
		if ( strlen( $this->name ) > 100 ) {
			throw new ValidationException('name', 'Namnet får inte vara längre än 100 bokstäver.');
		}
	}
}