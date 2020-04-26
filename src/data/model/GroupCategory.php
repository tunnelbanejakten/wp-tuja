<?php

namespace tuja\data\model;


use ReflectionClass;
use ReflectionException;
use tuja\data\store\CompetitionDao;
use tuja\util\rules\GroupCategoryRules;
use tuja\util\rules\PassthroughRuleSet;
use tuja\util\rules\RuleSet;

class GroupCategory {
	public $id;
	public $competition_id;
	public $name;
	private $rules;

	public function set_rules( GroupCategoryRules $rules ) {
		$this->rules = $rules;
	}

	public function get_rules(): GroupCategoryRules {
		if ( isset( $this->rules ) ) {
			return $this->rules;
		} else {
			return new GroupCategoryRules( [] );
		}
	}

	public function validate() {
		if ( strlen( trim( $this->name ) ) < 1 ) {
			throw new ValidationException( 'name', 'Namnet måste fyllas i.' );
		}
		if ( strlen( $this->name ) > 100 ) {
			throw new ValidationException( 'name', 'Namnet får inte vara längre än 100 bokstäver.' );
		}
	}
}