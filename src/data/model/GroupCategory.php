<?php

namespace tuja\data\model;


use tuja\util\rules\GroupCategoryRules;
use tuja\util\rules\YoungParticipantsRuleSet;
use tuja\util\fee\CompetingParticipantFeeCalculator;
use tuja\util\fee\GroupFeeCalculator;


class GroupCategory {
	public $id;
	public $competition_id;
	public $name;
	public $fee_calculator;
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

	public static function sample(): GroupCategory {
		$sample       = new GroupCategory();
		$sample->name = 'Category';
		$sample->set_rules( GroupCategoryRules::from_rule_set( new YoungParticipantsRuleSet(), new Competition() ) );

		return $sample;
	}
}