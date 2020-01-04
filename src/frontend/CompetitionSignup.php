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
use tuja\frontend\router\GroupHomeInitiator;
use tuja\frontend\router\GroupPeopleEditorInitiator;
use tuja\util\rules\RuleEvaluationException;
use tuja\view\FieldChoices;
use tuja\view\FieldEmail;
use tuja\view\FieldPhone;
use tuja\view\FieldPno;
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

				$group_home_link  = GroupHomeInitiator::link( $new_group );
				$edit_people_link = GroupPeopleEditorInitiator::link( $new_group );
				$support_email    = get_bloginfo( 'admin_email' );

				$this->group_dao->run_registration_rules( $new_group );

				if ( $new_group->get_status() == Group::STATUS_AWAITING_APPROVAL ) {
					printf( '
						<p class="tuja-message tuja-message-warning">Ert lag står på väntelistan.</p>
						<p>
							Det är många som vill vara med i Tunnelbanejakten i år och vi har tyvärr fullt just nu, men
							vi jobbar febrilt på att hitta ytterligare funktionärer så att vi kan öppna upp för fler
							lag. Om du känner någon som kan tänka sig att ställa upp som funktionärer så får du gärna
							höra av dig till <a href="mailto:%s">%s</a>.
						</p>
						<p>
							På <a href="%s" id="tuja_group_home_link" data-group-id="%d" data-group-key="%s">%s</a> kan ni se statusen för
							er anmälan men vi kontaktar er även via e-post när ni tagits bort från väntelistan.
						</p>
						<p>
							Vi har också skickat länken till din e-postadress.
						</p>', $support_email, $support_email, $group_home_link, $new_group->id, $new_group->random_id, $group_home_link );
				} else {
					printf( '
						<p class="tuja-message tuja-message-success">Tack för er anmälan!</p>
						<p>
							Ni måste nu fylla i vad de andra deltagarna i ert lag heter här:
							<a href="%s" id="tuja_edit_people_link">%s</a>
						</p>
						<p>
							På <a href="%s" id="tuja_group_home_link" data-group-id="%d" data-group-key="%s">%s</a> kan ni göra andra 
							administrativa saker, bland annat byta lagets namn eller tävlingsklass om det skulle behövas.
						</p>
						<p>
							Vi har också skickat länkarna till din e-postadress.
						</p>', $edit_people_link, $edit_people_link, $group_home_link, $new_group->id, $new_group->random_id, $group_home_link );
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

		$form = $this->get_form_html( $errors );

		$submit_button = $this->get_submit_button_html();

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

		$person_name_question = new FieldPno( 'Vad har du för födelsedag?', 'Vi rekommenderar dig att fylla i fullständigt personnummer.', false, true );
		$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PNO, $errors[ self::FIELD_PERSON_PNO ] );

		$html_sections[] = $this->get_recaptcha_html();

		return join( $html_sections );
	}

	private function get_submit_button_html() {
		$is_automatically_accepted = $this->get_competition()->initial_group_status !== Group::STATUS_AWAITING_APPROVAL;

		return sprintf( '
			<div class="tuja-buttons">
				<button type="submit" name="%s" value="%s" id="tuja_signup_button">%s</button>
			</div>
			%s',
			self::ACTION_BUTTON_NAME,
			self::ACTION_NAME_SAVE,
			$is_automatically_accepted ? 'Anmäl lag' : 'Anmäl lag till väntelista',
			$is_automatically_accepted ? '' : '<p class="tuja-message tuja-message-warning">Varför väntelista? Jo, så många har anmält sig att vi just nu kan vi inte ta emot fler lag. Ni kan dock anmäla laget till väntelistan och så hör vi av oss om läget förändras.</p>' );
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
		$new_person->pno   = $_POST[ self::FIELD_PERSON_PNO ];
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