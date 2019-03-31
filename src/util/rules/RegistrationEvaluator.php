<?php

namespace tuja\util\rules;


use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\Person;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\PersonDao;

class RegistrationEvaluator {
	private $person_dao;
	private $group_categories;

	public function __construct( $competition_id ) {
		$this->person_dao       = new PersonDao();
		$this->group_categories = $this->get_group_categories( $competition_id );
	}

	private function get_group_categories( $competition_id ) {
		$group_category_dao = new GroupCategoryDao();
		$categories         = $group_category_dao->get_all_in_competition( $competition_id );

		return array_combine( array_map( function ( GroupCategory $category ) {
			return $category->id;
		}, $categories ), array_values( $categories ) );
	}

	public function evaluate( Group $group ) {
		$group_category = $this->group_categories[ $group->category_id ];
		$people         = $this->person_dao->get_all_in_group( $group->id );

		return array_merge(
			self::rule_has_contacts( $people ),
			self::rule_person_names( $people ),
			self::rule_contacts_have_phone_and_email( $people ),
			self::rule_group_size( $people )
		);
	}

	private static function rule_has_contacts( array $people ) {
		$contacts = array_filter( $people, function ( Person $person ) {
			return $person->is_group_contact;
		} );
		switch ( count( $contacts ) ) {
			case 0:
				return [ new RuleResult( 'Kontaktperson', RuleResult::BLOCKER, 'Kontaktperson saknas.' ) ];
			case 1:
				return [ new RuleResult( 'Kontaktperson', RuleResult::OK, 'En kontaktperson har angivits.' ) ];
			case 2:
				return [ new RuleResult( 'Kontaktperson', RuleResult::WARNING, 'Ni har angett att två personer är kontaktpersoner. Vanligtvis räcker det med en.' ) ];
			default:
				return [ new RuleResult( 'Kontaktperson', RuleResult::BLOCKER, 'Bara en eller två personer kan vara kontaktpersoner.' ) ];
		}
	}

	private static function rule_contacts_have_phone_and_email( array $people ) {
		$contacts = array_filter( $people, function ( Person $person ) {
			return $person->is_group_contact;
		} );

		return array_reduce( $contacts, function ( $carry, Person $person ) {
			$rule_name = 'Kontaktpersonen ' . htmlspecialchars( $person->name );
			if ( ! empty( $person->phone ) ) {
				if ( Person::is_valid_phone_number( $person->phone ) ) {
					$carry[] = new RuleResult( $rule_name, RuleResult::OK, 'Telefonnumret till kontaktperson ser rimligt ut.' );
				} else {
					$carry[] = new RuleResult( $rule_name, RuleResult::WARNING, 'Telefonnumret till kontaktperson verkar inte vara korrekt.' );
				}
			} else {
				$carry[] = new RuleResult( $rule_name, RuleResult::BLOCKER, 'Kontaktperson måste ha ett telefonnummer.' );
			}
			if ( ! empty( $person->email ) ) {
				if ( Person::is_valid_email_address( $person->email ) ) {
					$carry[] = new RuleResult( $rule_name, RuleResult::OK, 'E-postadress till kontaktperson ser rimlig ut.' );
				} else {
					$carry[] = new RuleResult( $rule_name, RuleResult::WARNING, 'E-postadress till kontaktperson verkar inte vara korrekt.' );
				}
			} else {
				$carry[] = new RuleResult( $rule_name, RuleResult::BLOCKER, 'Kontaktperson måste ha en e-postadress.' );
			}

			return $carry;
		}, [] );
	}

	private static function rule_group_size( array $people ) {
		$participants = array_filter( $people, function ( Person $person ) {
			return $person->is_competing;
		} );
		if ( count( $participants ) < 4 ) {
			return [ new RuleResult( 'Antal deltagare', RuleResult::WARNING, 'Ni har färre än fyra deltagare. Det blir lättare, och säkert roligare, om ni är fler.' ) ];
		} elseif ( count( $participants ) > 8 ) {
			return [ new RuleResult( 'Antal deltagare', RuleResult::WARNING, 'Vi tycker lag ska ha högst åtta deltagare.' ) ];
		} else {
			return [ new RuleResult( 'Antal deltagare', RuleResult::OK, 'Ni verkar ha lagom många deltagare.' ) ];
		}
	}

	private static function rule_person_names( array $people ) {
		$names = array_map( function ( Person $person ) {
			return trim( $person->name );
		}, $people );
		if ( count( $people ) > 1 && count( $names ) != count( array_unique( $names ) ) ) {
			return [ new RuleResult( 'Deltagarnas namn', RuleResult::WARNING, 'Stämmer det verkligen att några i laget har samma namn?' ) ];
		} else {
			return [ new RuleResult( 'Deltagarnas namn', RuleResult::OK, 'Alla deltagare har vettiga namn.' ) ];
		}
	}

}