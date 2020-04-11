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
use tuja\Frontend;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\frontend\router\GroupPeopleEditorInitiator;
use tuja\util\rules\RuleEvaluationException;
use tuja\util\Strings;
use tuja\util\Template;
use tuja\view\FieldChoices;
use tuja\view\FieldEmail;
use tuja\view\FieldPhone;
use tuja\view\FieldPno;
use tuja\view\FieldText;

// TODO: Unify error handling so that there is no mix of "arrays of error messages" and "exception throwing". Pick one practice, don't mix.
class CompetitionSignup extends FrontendView {
	const ROLE_LABEL_GROUP_LEADER = 'Jag kommer vara med och tävla';
	const ROLE_LABEL_EXTRA_CONTACT = 'Jag administrerar bara lagets anmälan';
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

	public static function params_accepted( Group $group ): array {
		$group_home_link  = GroupHomeInitiator::link( $group );
		$edit_people_link = GroupPeopleEditorInitiator::link( $group );

		return [
			'edit_people_link' => sprintf( '<a href="%s" id="tuja_edit_people_link">%s</a>', $edit_people_link, $edit_people_link ),
			'group_home_link'  => sprintf( '<a href="%s" id="tuja_group_home_link" data-group-id="%d" data-group-key="%s">%s</a>', $group_home_link, $group->id, $group->random_id, $group_home_link )
		];
	}

	public static function params_awaiting_approval( Group $group ): array {
		$group_home_link = GroupHomeInitiator::link( $group );
		$support_email   = get_bloginfo( 'admin_email' );

		return [
			'support_email'   => sprintf( '<a href="mailto:%s">%s</a>', $support_email, $support_email ),
			'group_home_link' => sprintf( '<a href="%s" id="tuja_group_home_link" data-group-id="%d" data-group-key="%s">%s</a>', $group_home_link, $group->id, $group->random_id, $group_home_link )
		];
	}

	function get_content() {
		try {
			Strings::init( $this->get_competition()->id );

			return parent::get_content();
		} catch ( Exception $e ) {
			return $this->get_exception_message_html( $e );
		}
	}

	function output() {
		global $wpdb;

		Frontend::use_script( 'jquery' );
		Frontend::use_script( 'tuja-competition-signup.js' );

		$competition = $this->get_competition();
		$errors      = [];

		if ( @$_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE ) {
			try {
				$this->validate_recaptcha_html();

				// TODO: It's a bit odd that create_group and delete_person throw exceptions whereas update_group returns an arror of error messages.
				$new_group = $this->create_group();

				$this->group_dao->run_registration_rules( $new_group );

				if ( $new_group->get_status() == Group::STATUS_AWAITING_APPROVAL ) {
					printf( '<p class="tuja-message tuja-message-warning">%s</p>', Strings::get( 'competition_signup.submitted.awaiting_approval.warning_message' ) );
					print Strings::get( 'competition_signup.submitted.awaiting_approval.body_text', self::params_awaiting_approval( $new_group ) );
				} else {
					printf( '<p class="tuja-message tuja-message-success">%s</p>', Strings::get( 'competition_signup.submitted.accepted.success_message' ) );
					print Strings::get( 'competition_signup.submitted.accepted.body_text', self::params_accepted( $new_group ) );
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

		$intro = Strings::get( 'competition_signup.intro.body_text' );
		$intro = ! empty( $intro ) ? Template::string( $intro )->render( [], true ) : '';

		$fineprint = Strings::get( 'competition_signup.fineprint.body_text' );
		$fineprint = ! empty( $fineprint ) ? sprintf( '<div class="tuja-fineprint">%s</div>', Template::string( $fineprint )->render( [], true ) ) : '';

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

		$group_name_question = new FieldText( 'Vad heter laget?', null, false, [] );
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
					'Vilken klass tävlar ni i?',
					null,
					false,
					$group_category_options,
					false );
				$html_sections[]         = $this->render_field( $group_category_question, self::FIELD_GROUP_AGE, $errors[ self::FIELD_GROUP_AGE ] );
				break;
		}

		$reporter_role   = new FieldChoices(
			'Vem är du?',
			null,
			false,
			[
				self::ROLE_LABEL_GROUP_LEADER,
				self::ROLE_LABEL_EXTRA_CONTACT
			],
			false );
		$html_sections[] = $this->render_field( $reporter_role, self::FIELD_PERSON_ROLE, $errors[ self::FIELD_PERSON_ROLE ], $_POST[ self::FIELD_PERSON_ROLE ] ?: self::ROLE_LABEL_GROUP_LEADER );

		$person_name_question = new FieldEmail( 'Vilken e-postadress har du?', Strings::get( 'competition_signup.form.email.hint' ), false );
		$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_EMAIL, $errors[ self::FIELD_PERSON_EMAIL ] );

		$person_name_question = new FieldText( 'Vad heter du?', null, false, [] );
		$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_NAME, $errors[ self::FIELD_PERSON_NAME ] );

		$person_phone_question = new FieldPhone( 'Vilket telefonnummer har du?', Strings::get( 'competition_signup.form.phone.hint' ), false );
		$html_sections[]       = $this->render_field( $person_phone_question, self::FIELD_PERSON_PHONE, $errors[ self::FIELD_PERSON_PHONE ] );

		$person_name_question = new FieldPno( 'Vad har du för födelsedag?', Strings::get( 'person.form.pno.hint' ), false );
		$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PNO, $errors[ self::FIELD_PERSON_PNO ] );

		$html_sections[] = $this->get_recaptcha_html();

		return join( $html_sections );
	}

	private function get_submit_button_html() {
		$is_automatically_accepted = $this->get_competition()->initial_group_status !== Group::STATUS_AWAITING_APPROVAL;

		$label = $is_automatically_accepted
			? Strings::get( 'competition_signup.default.button.label' )
			: Strings::get( 'competition_signup.waiting_list.button.label' );

		$warning = $is_automatically_accepted
			? Strings::get( 'competition_signup.default.warning_message' )
			: Strings::get( 'competition_signup.waiting_list.warning_message' );

		return sprintf( '
			<div class="tuja-buttons">
				<button type="submit" name="%s" value="%s" id="tuja_signup_button">%s</button>
			</div>
			%s',
			self::ACTION_BUTTON_NAME,
			self::ACTION_NAME_SAVE,
			$label,
			! empty( $warning ) ? sprintf( '<p class="tuja-message tuja-message-warning">%s</p>', $warning ) : '' );
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
		$new_person->email = $_POST[ self::FIELD_PERSON_EMAIL ];
		if ( $_POST[ self::FIELD_PERSON_ROLE ] == self::ROLE_LABEL_EXTRA_CONTACT ) {
			$new_person->name = $_POST[ self::FIELD_PERSON_EMAIL ];
			$new_person->set_as_extra_contact();
		} else {
			$new_person->name  = $_POST[ self::FIELD_PERSON_NAME ];
			$new_person->phone = $_POST[ self::FIELD_PERSON_PHONE ];
			$new_person->pno   = $_POST[ self::FIELD_PERSON_PNO ];
			$new_person->set_as_group_leader();
		}

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