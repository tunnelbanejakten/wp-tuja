<?php

namespace tuja\util\rules;


use DateTime;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\PersonDao;
use tuja\util\DateUtils;
use tuja\view\EditGroupShortcode;

class RegistrationEvaluator {
	private $person_dao;

	public function __construct() {
		$this->person_dao = new PersonDao();
	}

	public function evaluate( Group $group ) {
		$people = $this->person_dao->get_all_in_group( $group->id );

		return array_merge(
			self::rule_contacts_defined( $group, $people ),
			self::rule_contacts_have_phone_and_email( $people ),
			self::rule_person_names( $people ),
			self::rule_person_pno( $people ),
			self::rule_adult_supervision( $group, $people ),
			self::rule_group_size( $people )
		);
	}

	private static function rule_person_pno( array $people ) {
		return array_reduce( $people, function ( $carry, Person $person ) {
			if ( $person->is_competing ) {
				$rule_name = 'Deltagare ' . htmlspecialchars( $person->name );
				if ( ! empty( $person->pno ) ) {
					try {
						$pno  = DateUtils::fix_pno( $person->pno );
						$date = DateTime::createFromFormat( 'Ymd', substr( $pno, 0, 8 ) );
						if ( $date !== false ) {
							if ( substr( $pno, 9, 4 ) === '0000' ) {
								$carry[] = new RuleResult( $rule_name, RuleResult::WARNING, 'Vi rekommenderar att ange hela personnumret.' );
							}
						} else {
							$carry[] = new RuleResult( $rule_name, RuleResult::WARNING, 'Personnummer/födelsedag verkar inte vara korrekt.' );
						}
					} catch ( Exception $e ) {
						$carry[] = new RuleResult( $rule_name, RuleResult::WARNING, 'Personnummer/födelsedag verkar inte vara korrekt.' );
					}
				} else {
					$carry[] = new RuleResult( $rule_name, RuleResult::BLOCKER, 'Personnummer/födelsedag har inget angetts.' );
				}
			}

			return $carry;
		}, [] );
	}

	private static function rule_adult_supervision( Group $group, array $people ) {
		$children           = array_filter( $people, function ( Person $person ) {
			return isset( $person->age ) && $person->is_competing && $person->age < 15;
		} );
		$adults             = array_filter( $people, function ( Person $person ) {
			return isset( $person->age ) && $person->age >= 18;
		} );
		$adult_participants = array_filter( $people, function ( Person $person ) {
			return $person->is_competing && isset( $person->age ) && $person->age >= 18;
		} );

		$count_children           = count( $children );
		$count_adults             = count( $adults );
		$count_adult_participants = count( $adult_participants );
		if ( $count_children > 3 && $count_adult_participants > 0 ) {
			return [
				new RuleResult(
					'Vuxen i laget',
					RuleResult::WARNING,
					'Laget har ' . $count_children . ' tävlande barn och ' . $count_adult_participants .
					' tävlande vuxna. Kryssa i "' . EditGroupShortcode::ROLE_ISNOTCOMPETING_LABEL . '" för vuxna som inte tävlar, och som därför inte behöver betala anmälningsavgift.'
				)
			];
		} elseif ( $count_children > 0 && $count_adults == 0 ) {
			if ( $group->age_competing_avg < 15 ) {
				return [ new RuleResult( 'Vuxen i laget', RuleResult::BLOCKER, 'Laget måste ha med sig en vuxen under dagen eftersom de flesta är under 15.' ) ];
			} else {
				// Not all are under 15 but at least one is.
				return [ new RuleResult( 'Vuxen i laget', RuleResult::WARNING, 'Vi rekommenderar att alla lag med deltagare under 15 också har med sig en vuxen under dagen.' ) ];
			}
		}

		return [ new RuleResult( 'Vuxen i laget', RuleResult::OK, 'Laget uppfyller våra krav för när det måste finnas vuxna i laget.' ) ];
	}

	private static function rule_contacts_defined( Group $group, array $people ) {
		$contacts = array_filter( $people, function ( Person $person ) {
			return $person->is_group_contact;
		} );
		switch ( count( $contacts ) ) {
			case 0:
				$potential_contacts = array_filter( $people, function ( Person $person ) {
					return ( ! empty( $person->email ) && Person::is_valid_email_address( $person->email ) )
					       || ( ! empty( $person->phone ) && Person::is_valid_phone_number( $person->phone ) );
				} );
				if ( count( $potential_contacts ) > 0 ) {
					return [ new RuleResult( 'Kontaktperson', RuleResult::BLOCKER, 'Ingen av deltagarna med telefonnummer/e-postadress har markerats som er kontaktperson/lagledare.' ) ];
				} else {
					return [ new RuleResult( 'Kontaktperson', RuleResult::BLOCKER, 'Kontaktperson/lagledare saknas.' ) ];
				}
			case 1:
				if ( array_pop( $contacts )->is_competing ) {
					return [ new RuleResult( 'Kontaktperson', RuleResult::OK, 'En kontaktperson/lagledare har angivits.' ) ];
				} else {
					if ( $group->age_competing_avg < 15 ) {
						return [ new RuleResult( 'Kontaktperson', RuleResult::WARNING, 'Vi rekommenderar att även en av de tävlande är kontaktperson och lagledare.' ) ];
					} else {
						return [ new RuleResult( 'Kontaktperson', RuleResult::WARNING, 'Vi rekommenderar att en av de tävlande är kontaktperson och lagledare.' ) ];
					}
				}
			case 2:
				if ( $group->age_competing_avg < 15 ) {
					return [ new RuleResult( 'Kontaktperson', RuleResult::OK, 'Två kontaktpersoner har angivits.' ) ];
				} else {
					return [ new RuleResult( 'Kontaktperson', RuleResult::WARNING, 'Ni har angett att två personer är kontaktpersoner och lagledare. Vanligtvis räcker det med en.' ) ];
				}
			default:
				return [ new RuleResult( 'Kontaktperson', RuleResult::BLOCKER, 'Bara en eller två personer kan vara kontaktpersoner/lagledare.' ) ];
		}
	}

	private static function rule_contacts_have_phone_and_email( array $people ) {
		$contacts = array_filter( $people, function ( Person $person ) {
			return $person->is_group_contact;
		} );

		if ( count( $contacts ) == 0 ) {
			// Nothing to do here. Function rule_contacts_defined deals with groups without contacts.
			return [];
		}

		$results              = [];
		$is_phone_contact_set = false;
		$is_email_contact_set = false;

		foreach ( $contacts as $person ) {

			$rule_name = 'Kontaktpersonen ' . htmlspecialchars( $person->name );
			if ( ! empty( $person->phone ) ) {
				if ( Person::is_valid_phone_number( $person->phone ) ) {
					$results[]            = new RuleResult( $rule_name, RuleResult::OK, 'Telefonnumret till kontaktperson ser rimligt ut.' );
					$is_phone_contact_set = true;
				} else {
					$results[] = new RuleResult( $rule_name, RuleResult::WARNING, 'Telefonnumret till kontaktperson verkar inte vara korrekt.' );
				}
			}
			if ( ! empty( $person->email ) ) {
				if ( Person::is_valid_email_address( $person->email ) ) {
					$results[]            = new RuleResult( $rule_name, RuleResult::OK, 'E-postadress till kontaktperson ser rimlig ut.' );
					$is_email_contact_set = true;
				} else {
					$results[] = new RuleResult( $rule_name, RuleResult::WARNING, 'E-postadress till kontaktperson verkar inte vara korrekt.' );
				}
			}
		}

		if ( ! $is_phone_contact_set && ! $is_email_contact_set ) {
			$results[] = new RuleResult( 'Kontaktperson', RuleResult::BLOCKER, 'Ni saknar telefonnummer och e-post för kontaktperson.' );
		} elseif ( ! $is_phone_contact_set ) {
			$results[] = new RuleResult( 'Kontaktperson', RuleResult::BLOCKER, 'Ni har ingen kontaktperson med (giltigt) telefonnummer.' );
		} elseif ( ! $is_email_contact_set ) {
			$results[] = new RuleResult( 'Kontaktperson', RuleResult::BLOCKER, 'Ni har ingen kontaktperson med (rimlig) e-postadress.' );
		}

		return $results;
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