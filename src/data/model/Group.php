<?php

namespace tuja\data\model;


use tuja\util\GroupCategoryCalculator;

class Group
{
	private static $group_calculators = [];

	public $id;
	public $random_id;
	public $competition_id;
	public $name;
	public $category_id;
	public $age_competing_avg;
	public $age_competing_stddev;
	public $age_competing_min;
	public $age_competing_max;
	public $count_competing;
	public $count_follower;
	public $count_team_contact;

	public function validate() {
		if ( strlen(trim($this->name)) < 1) {
			throw new ValidationException('name', 'Namnet måste fyllas i.');
		}
		if ( strlen($this->name) > 100) {
			throw new ValidationException('name', 'Namnet får inte vara längre än 100 bokstäver.');
		}
	}

	public function get_derived_group_category() {
		return self::get_group_calculator( $this->competition_id )->get_category( $this );
	}

	private static function get_group_calculator( $competition_id ): GroupCategoryCalculator {
		if ( ! isset( self::$group_calculators[ $competition_id ] ) ) {
			self::$group_calculators[ $competition_id ] = new GroupCategoryCalculator( $competition_id );
		}

		return self::$group_calculators[ $competition_id ];
	}

}