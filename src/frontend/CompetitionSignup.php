<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\competition;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PersonDao;
use tuja\frontend\router\GroupPeopleEditorInitiator;
use tuja\util\rules\RuleEvaluationException;
use tuja\view\FieldChoices;
use tuja\view\FieldEmail;
use tuja\view\FieldPhone;
use tuja\view\FieldText;

// TODO: Unify error handling so that there is no mix of "arrays of error messages" and "exception throwing". Pick one practice, don't mix.
class CompetitionSignup extends FrontendView {
	private $competition_key;
	private $competition_dao;

	const FIELD_PREFIX_PERSON = 'tuja-person__';
	const FIELD_PREFIX_GROUP = 'tuja-group__';
	private $person_dao;
	private $group_dao;

	public function __construct( $url, $competition_key ) {
		parent::__construct( $url );
		$this->competition_key = $competition_key;
		$this->competition_dao = new CompetitionDao();
		$this->person_dao      = new PersonDao();
		$this->group_dao       = new GroupDao();
	}

	function output() {
		global $wpdb;

		$competition = $this->get_competition();
		$errors      = [];

		if ( @$_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE ) {
			try {
				$this->validate_recaptcha_html();

				// TODO: It's a bit odd that create_group and delete_person throw exceptions whereas update_group returns an arror of error messages.
				$new_group = $this->create_group();

				$edit_link = GroupPeopleEditorInitiator::link( $new_group );
				if ( ! empty( $edit_link ) ) {
					printf( '<p class="tuja-message tuja-message-success">Tack! Nästa steg är att gå till <a href="%s">%s</a> och fylla i vad de andra deltagarna i ert lag heter. Vi har också skickat länken till din e-postadress så att du kan ändra er anmälan framöver.</p>', $edit_link, $edit_link );
				} else {
					printf( '<p class="tuja-message tuja-message-success">Tack för din anmälan.</p>' );
				}

				return;
			} catch ( ValidationException $e ) {
				$errors = [ $e->getField() => $e->getMessage() ];
			} catch ( RuleEvaluationException $e ) {
				$errors = [ '__' => $e->getMessage() ];
			} catch ( Exception $e ) {
				// TODO: Create helper method for generating field names based on "group or person" and attribute name.
				$errors = [ '__' => $e->getMessage() ];
			}
		}

		$errors_overall = isset( $errors['__'] ) ? sprintf( '<p class="tuja-message tuja-message-error">%s</p>', $errors['__'] ) : '';

		$form           = $this->get_form_html( $errors );

		$submit_button  = $this->get_submit_button_html();

		include( 'views/competition-signup.php' );
	}

	function get_title() {
		return sprintf( 'Anmäl er till %s', $this->get_competition()->name );
	}

	function get_competition(): Competition {
		return $this->competition_dao->get_by_key( $this->competition_key );
	}

	private function get_form_html( $errors = array() ): string {
		$html_sections = [];

		$group_name_question = new FieldText( 'Vad heter ert lag?', null, false, [], true );
		$html_sections[]     = $this->render_field( $group_name_question, self::FIELD_GROUP_NAME, $errors[ self::FIELD_GROUP_NAME ] );

		$categories = $this->get_categories( $this->get_competition()->id );

		$group_category_options = array_map( function ( $category ) {
			return $category->name;
		}, $categories );

		switch ( count( $group_category_options ) ) {
			case 0:
				break;
			case 1:
				$html_sections[] = sprintf( '<input type="hidden" name="%s" value="%s">', self::FIELD_GROUP_AGE, htmlentities( $group_category_options[0] ) );
				break;
			default:
				$group_category_question = new FieldChoices(
					null,
					null,
					false,
					$group_category_options,
					false );
				$html_sections[]         = $this->render_field( $group_category_question, self::FIELD_GROUP_AGE, $errors[ self::FIELD_GROUP_AGE ] );
				break;
		}

		$person_name_question = new FieldText( 'Vad heter du?', null, false, [], true );
		$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_NAME, $errors[ self::FIELD_PERSON_NAME ] );

		$person_name_question = new FieldEmail( 'Vilken e-postadress har du?', 'Vi kommer skicka viktig information inför tävlingen till denna adress. Ni kan ändra e-postadress senare om det skulle behövas.', false, true );
		$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_EMAIL, $errors[ self::FIELD_PERSON_EMAIL ] );

		$person_phone_question = new FieldPhone( 'Vilket telefonnummer har du?', 'Vi kommer skicka viktig information under tävlingen till detta nummer. Ni kan ändra telefonnummer senare om det skulle behövas.', false, true );
		$html_sections[]       = $this->render_field( $person_phone_question, self::FIELD_PERSON_PHONE, $errors[ self::FIELD_PERSON_PHONE ] );

		$html_sections[] = $this->get_recaptcha_html();

		return join( $html_sections );
	}

	private function get_submit_button_html() {
		return sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="%s">%s</button></div>', self::ACTION_BUTTON_NAME, self::ACTION_NAME_SAVE, 'Anmäl lag' );
	}

	// TODO: create_group does a bit too much application logic to be in a presentation class. Extract application logic to some utility class.
	private function create_group(): Group {
		// INIT
		$category = $this->get_posted_category( $this->get_competition()->id );
		if ( ! isset( $category ) ) {
			throw new ValidationException( self::FIELD_GROUP_AGE, 'No category selected.' );
		}
		// DETERMINE REQUESTED CHANGES
		$new_group = new Group();
		$new_group->set_status( Group::DEFAULT_STATUS );
		$new_group->name           = $_POST[ self::FIELD_GROUP_NAME ];
		$new_group->competition_id = $this->get_competition()->id;
		if ( isset( $category ) ) {
			$new_group->category_id = $category->id;
		}

		try {
			$new_group->validate();
		} catch ( ValidationException $e ) {
			throw new ValidationException( self::FIELD_PREFIX_GROUP . $e->getField(), $e->getMessage() );
		}

		$new_person = new Person();
		$new_person->set_status( Person::DEFAULT_STATUS );
		$new_person->name  = $_POST[ self::FIELD_PERSON_NAME ];
		$new_person->email = $_POST[ self::FIELD_PERSON_EMAIL ];
		$new_person->phone = $_POST[ self::FIELD_PERSON_PHONE ];
		$new_person->set_as_group_leader();

		try {
			// Person is validated before Group is created in order to catch simple input problems, like a missing name or email address.
			$new_person->validate( $category->get_rule_set() );
		} catch ( ValidationException $e ) {
			throw new ValidationException( self::FIELD_PREFIX_PERSON . $e->getField(), $e->getMessage() );
		}

		if ( ! $this->is_create_allowed( $this->get_competition(), $category ) ) {
			throw new RuleEvaluationException( 'Anmälan är tyvärr stängd' );
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
					throw new Exception( 'Ett fel uppstod. Vi vet tyvärr inte riktigt varför.' );
				}
			} catch ( ValidationException $e ) {
				throw new ValidationException( self::FIELD_PREFIX_PERSON . $e->getField(), $e->getMessage() );
			}
		} else {
			throw new Exception( 'Kunde inte anmäla laget.' );
		}
	}

}