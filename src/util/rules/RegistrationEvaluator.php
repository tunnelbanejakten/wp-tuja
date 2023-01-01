<?php

namespace tuja\util\rules;


use DateTime;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\PersonDao;
use tuja\util\DateUtils;

class RegistrationEvaluator {
	private $person_dao;
	private $group_rules;

	public function __construct( GroupCategoryRules $group_rules ) {
		$this->person_dao  = new PersonDao();
		$this->group_rules = $group_rules;
	}

	public function evaluate( Group $group ) {
		$people = $this->person_dao->get_all_in_group( $group->id );

		return array_merge(
			$this->rule_contacts_defined( $group, $people ),
			$this->rule_contacts_have_phone_and_email( $people ),
			$this->rule_person_names( $people ),
			$this->rule_person_pno( $people ),
			$this->rule_adult_supervision( $group, $people ),
			$this->rule_group_size( $people )
		);
	}

	private function rule_person_pno( array $people ) {
		return array_reduce( $people, function ( $carry, Person $person ) {
			if ( $person->is_competing() ) {
				$rule_name = 'Deltagare ' . htmlspecialchars( $person->name );
				if ( ! empty( $person->pno ) ) {
					try {
						// TODO: Only run this rule if _full_ PNO/NIN is required/optional.
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
					$carry[] = new RuleResult( $rule_name, RuleResult::BLOCKER, 'Personnummer eller födelsedag måste anges.' );
				}
			}

			return $carry;
		}, [] );
	}

	private function rule_adult_supervision( Group $group, array $people ) {
		$adults             = array_filter( $people, function ( Person $person ) {
			return $person->is_adult();
		} );
		$adult_participants = array_filter( $people, function ( Person $person ) {
			return $person->is_adult() && $person->is_competing();
		} );

		$count_adults             = count( $adults );
		$count_adult_participants = count( $adult_participants );
		if ( $this->group_rules->is_adult_supervisor_required() ) {
			if ( $count_adults == 0 ) {
				return [ new RuleResult( 'Vuxen som följer med', RuleResult::BLOCKER, 'Laget måste ha med sig en vuxen under dagen.' ) ];
			} elseif ( $count_adult_participants > 0 ) {
				return [
					new RuleResult(
						'Vuxen som följer med',
						RuleResult::BLOCKER,
						'Vuxna som bara följer med ska inte anges som lagmedlemmar.'
					)
				];
			}
		}

		return [];
	}

	private function rule_contacts_defined( Group $group, array $people ) {
		$contacts = array_filter( $people, function ( Person $person ) {
			return $person->is_contact();
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
				if ( array_pop( $contacts )->is_competing() ) {
					return [ new RuleResult( 'Kontaktperson', RuleResult::OK, 'En kontaktperson/lagledare har angivits.' ) ];
				} else {
					if ( $this->group_rules->is_adult_supervisor_required() ) {
						return [ new RuleResult( 'Kontaktperson', RuleResult::WARNING, 'Vi rekommenderar att även en av de tävlande är kontaktperson och lagledare.' ) ];
					} else {
						return [ new RuleResult( 'Kontaktperson', RuleResult::WARNING, 'Vi rekommenderar att en av de tävlande är kontaktperson och lagledare.' ) ];
					}
				}
			default:
				return [];
		}
	}

	private function rule_contacts_have_phone_and_email( array $people ) {
		$contacts = array_filter( $people, function ( Person $person ) {
			return $person->is_contact();
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

	private function rule_group_size( array $people ) {
		$participants = array_filter( $people, function ( Person $person ) {
			return $person->is_competing();
		} );
		list ( $min, $max ) = $this->group_rules->get_people_count_range(Person::PERSON_TYPE_LEADER, Person::PERSON_TYPE_REGULAR);
		if ( count( $participants ) < $min ) {
			return [ new RuleResult( 'Antal deltagare', RuleResult::BLOCKER, 'Ett lag måste ha minst ' . $min . ' tävlande. Kontakta ' . get_bloginfo( 'admin_email' ) . ' om detta skapar problem för er.' ) ];
		} elseif ( count( $participants ) > $max ) {
			return [ new RuleResult( 'Antal deltagare', RuleResult::BLOCKER, 'Ett lag får bara ha ' . $max . ' tävlande. Kontakta ' . get_bloginfo( 'admin_email' ) . ' om detta skapar problem för er.' ) ];
		} else {
			return [];
		}
	}

	private function rule_person_names( array $people ) {
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