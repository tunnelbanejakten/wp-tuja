<?php

namespace tuja\view;

use tuja\data\model\Group;
use tuja\util\GroupCategoryCalculator;

class AbstractShortcode {
	private $group_category_calculators = [];

	public function __construct() {
	}

	protected function get_group_category( Group $group ) {
		if ( ! isset( $this->group_category_calculators [ $group->competition_id ] ) ) {
			$this->group_category_calculators [ $group->competition_id ] = new GroupCategoryCalculator( $group->competition_id );
		}

		return $this->group_category_calculators [ $group->competition_id ]->get_category( $group );
	}
}