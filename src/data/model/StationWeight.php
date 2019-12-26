<?php


namespace tuja\data\model;


class StationWeight {
	public $from_station_id;
	public $to_station_id;
	public $weight;

	public function __construct( $from_station_id, $to_station_id, $weight ) {
		$this->from_station_id = $from_station_id;
		$this->to_station_id   = $to_station_id;
		$this->weight          = $weight;
	}
}