<?php

namespace tuja\util;

class Anonymizer {
	private $first_names;
	private $last_names;
	private $animals;
	private $neighborhoods;

	public function __construct() {
		$this->first_names   = file( __DIR__.'/data.firstnames.txt', FILE_IGNORE_NEW_LINES );
		$this->last_names    = file( __DIR__.'/data.lastnames.txt', FILE_IGNORE_NEW_LINES );
		$this->animals       = file( __DIR__.'/data.animals.txt', FILE_IGNORE_NEW_LINES );
		$this->neighborhoods = file( __DIR__.'/data.neighborhoods.txt', FILE_IGNORE_NEW_LINES );
	}

	public function first_name() {
		return $this->first_names[ rand( 0, count( $this->first_names ) - 1 ) ];
	}

	public function last_name() {
		return $this->last_names[ rand( 0, count( $this->last_names ) - 1 ) ];
	}

	public function animal() {
		return $this->animals[ rand( 0, count( $this->animals ) - 1 ) ];
	}

	public function neighborhood() {
		return $this->neighborhoods[ rand( 0, count( $this->neighborhoods ) - 1 ) ];
	}

	public function birthdate( $year = 2005 ) {
		return sprintf( '%d%02d%02d', $year, rand( 1, 12 ), rand( 1, 28 ) );
	}

	public function phone( $phone = '+46701234567' ) {
		return preg_replace( '/\\d{4}$/', '0000', $phone );
	}
}

//$anonymizer = new Anonymizer();
//echo $anonymizer->first_name();
//echo $anonymizer->last_name();
//echo $anonymizer->animal();
//echo $anonymizer->neighborhood();
//echo $anonymizer->birthdate();
//echo $anonymizer->phone();