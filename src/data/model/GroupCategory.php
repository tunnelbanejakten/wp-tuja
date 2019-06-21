<?php

namespace tuja\data\model;


use ReflectionClass;
use ReflectionException;

class GroupCategory
{
	public $id;
	public $competition_id;
	public $is_crew;
	public $name;
	public $rule_set_class_name;

	public function get_rule_set() {
		try {
			if ( isset( $this->rule_set_class_name ) && class_exists( $this->rule_set_class_name ) ) {
				return ( new ReflectionClass( $this->rule_set_class_name ) )->newInstance();
			}
		} catch ( ReflectionException $e ) {
		}

		return null;
	}

	public function validate() {
		if ( strlen( trim( $this->name ) ) < 1 ) {
			throw new ValidationException('name', 'Namnet måste fyllas i.');
		}
		if ( strlen( $this->name ) > 100 ) {
			throw new ValidationException('name', 'Namnet får inte vara längre än 100 bokstäver.');
		}
	}
}