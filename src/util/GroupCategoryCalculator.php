<?php

namespace tuja\util;


use tuja\data\model\Group;
use tuja\data\store\GroupCategoryDao;

class GroupCategoryCalculator {

	private $categories;
	private $calculators;

	public function __construct( $competition_id ) {
		$dao = new GroupCategoryDao();

		$this->categories  = $dao->get_all_in_competition( $competition_id );
		$this->calculators = self::create_age_range_category_calculators( $this->categories );
	}

	/**
	 * Creates an array of "group category calculators" based on numbers found in the
	 * category names. The numbers are assumed to be age ranges.
	 *
	 * Examples:
	 *
	 * A group category named "Ages -10" would be assigned to groups where
	 * the average participant age is AT MOST 10 years.
	 *
	 * A group category named "Ages 10-15" would be assigned to groups where
	 * the average participant age is BETWEEN 10 and 15 years.
	 *
	 * A group category named "Ages 15+" would be assigned to groups where
	 * the average participant age is AT LEAST 15 years.
	 */
	private static function create_age_range_category_calculators( $categories ) {
		$calculators = [];
		foreach ( $categories as $category ) {
			$matches = [];
			if ( preg_match( '/(\d*)[+-](\d*)/', $category->name, $matches ) === 1 ) {
				list( , $min, $max ) = $matches;
				$calculators[] = self::create_calculator( $min, $max, $category );
			}
		}

		return $calculators;
	}

	private static function create_calculator( $min, $max, $category ) {
		return function ( Group $group ) use ( $min, $max, $category ) {
			$is_lower_ok = ! is_numeric( $min ) || $min <= $group->age_competing_avg;
			$is_upper_ok = ! is_numeric( $max ) || $max >= $group->age_competing_avg;

			return $is_lower_ok && $is_upper_ok ? $category : null;
		};
	}

	public function get_category( Group $group ) {

		// Group has explicit category assigned. Find it and return it.
		if ( $group->category_id > 0 ) {
			foreach ( $this->categories as $category ) {
				if ( $category->id === $group->category_id ) {
					// Bingo! Found one. Return it.
					return $category;
				}
			}
		}

		// No category found. Try to find a suitable using the calculators (or return
		// null if one cannot be found).
		return array_reduce( $this->calculators, function ( $carry, $calculator ) use ( $group ) {
			return $carry ?: $calculator( $group );
		}, null );
	}

}