<?php

namespace tuja\controller;

use Exception;
use tuja\data\model\Competition;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupDao;
use tuja\data\model\ValidationException;
use tuja\util\rules\RuleEvaluationException;
use tuja\util\Strings;

class SignupController {
	function __construct() {
		$this->competition_dao      = new CompetitionDao();
		$this->group_dao            = new GroupDao();
	}

	private function create_group(): Group {
		// INIT
		$category = $this->get_posted_category( $this->get_competition()->id );
		if ( ! isset( $category ) ) {
			throw new ValidationException( self::FIELD_GROUP_AGE, Strings::get( 'competition_signup.error.no_category' ) );
		}
		if ( ! $category->get_rules()->is_create_registration_allowed() ) {
			throw new RuleEvaluationException( Strings::get( 'competition_signup.error.signup_closed' ) );
		}
		// DETERMINE REQUESTED CHANGES
		$new_group = new Group();
		$new_group->set_status( Group::DEFAULT_STATUS );
		$new_group->name           = @$_POST[ self::FIELD_GROUP_NAME ];
		$new_group->city           = @$_POST[ self::FIELD_GROUP_CITY ];
		$new_group->competition_id = $this->get_competition()->id;
		if ( isset( $category ) ) {
			$new_group->category_id = $category->id;
		}

		try {
			$new_group->validate();
		} catch ( ValidationException $e ) {
			throw new ValidationException( self::FIELD_PREFIX_GROUP . $e->getField(), $e->getMessage() );
		}

		$new_person = $this->get_posted_person();

		try {
			// Person is validated before Group is created in order to catch simple input problems, like a missing name or email address.
			$new_person->validate( $category->get_rules() );
		} catch ( ValidationException $e ) {
			throw new ValidationException( self::FIELD_PREFIX_PERSON . $e->getField(), $e->getMessage() );
		}

		// SAVE CHANGES
		$new_group_id = false;
		try {
			$new_group_id = $this->group_dao->create( $new_group );
		} catch ( ValidationException $e ) {
			throw new ValidationException( self::FIELD_PREFIX_GROUP . $e->getField(), $e->getMessage() );
		}
		if ( $new_group_id !== false ) {
			$new_person->group_id = $new_group_id;
			try {
				$new_person_id = $this->person_dao->create( $new_person );
				if ( $new_person_id !== false ) {

					$group = $this->group_dao->get( $new_group_id );

					if ( $this->get_competition()->initial_group_status !== null ) {
						// Change status from CREATED to $initial_group_status. This might trigger messages to be sent.
						$group->set_status( $this->get_competition()->initial_group_status );
						$this->group_dao->update( $group );
					}

					return $group;
				} else {
					throw new Exception( Strings::get( 'competition_signup.error.unknown' ) );
				}
			} catch ( ValidationException $e ) {
				throw new ValidationException( self::FIELD_PREFIX_PERSON . $e->getField(), $e->getMessage() );
			}
		} else {
			throw new Exception( Strings::get( 'competition_signup.error.no_group_id' ) );
		}
	}
}
