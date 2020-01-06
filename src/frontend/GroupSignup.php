<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\frontend\router\PersonEditorInitiator;
use tuja\util\messaging\EventMessageSender;
use tuja\util\Strings;
use tuja\view\FieldEmail;
use tuja\view\FieldPhone;
use tuja\view\FieldPno;
use tuja\view\FieldText;

class GroupSignup extends AbstractGroupView {
	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Anmäl dig till %s' );
	}

	function output() {
		try {
			$errors         = [];
			$errors_overall = '';
			$group          = $this->get_group();

			$this->check_group_status( $group );

			if ( ! $this->is_edit_allowed( $group ) ) {
				return sprintf( '<p class="tuja-message tuja-message-error">%s</p>', 'Tyvärr så går det inte att anmäla sig nu.' );
			}

			$real_category = $group->get_derived_group_category();

			$collect_contact_information = $real_category->get_rule_set()->is_contact_information_required_for_regular_group_member();
			$collect_ssn                 = $real_category->get_rule_set()->is_ssn_required();
			try {

				if ( $_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE ) {
					$this->validate_recaptcha_html();

					// TODO: It's a bit odd that create_group and delete_person throw exceptions whereas update_group returns an error of error messages.
					$new_person = $this->create_person( $group );

					$edit_link = PersonEditorInitiator::link( $group, $new_person );

					$this->send_person_welcome_mail( $new_person );

					if ( ! empty( $edit_link ) ) {
						printf( '<p class="tuja-message tuja-message-success">%s</p>',
							Strings::get( 'group_signup.success.message.with_link',
								sprintf( '<a href="%s" id="tuja_signup_success_edit_link">%s</a>',
									$edit_link,
									$edit_link ) ) );
					} else {
						printf( '<p class="tuja-message tuja-message-success">%s</p>',
							Strings::get( 'group_signup.success.message.without_link' ) );
					}

					$this->group_dao->run_registration_rules( $group );

					return;
				}
			} catch ( ValidationException $e ) {
				// TODO: Create helper method for generating field names based on "group or person" and attribute name.
				$errors = [ $e->getField() => $e->getMessage() ];
			} catch ( Exception $e ) {
				$errors_overall = $this->get_exception_message_html( $e );
			}


			$form = $this->get_form_html(
				true,
				$collect_contact_information,
				$collect_contact_information,
				$collect_ssn,
				true,
				self::ROLE_REGULAR_GROUP_MEMBER,
				$errors );

			$submit_button = $this->get_submit_button_html();

			include( 'views/group-signup.php' );
		} catch ( Exception $e ) {
			print $this->get_exception_message_html( $e );
		}
	}

	// TODO: DRY?
	private function get_form_html(
		bool $show_name = true,
		bool $show_email = true,
		bool $show_phone = true,
		bool $show_pno = true,
		bool $show_food = true,
		string $role = self::ROLE_REGULAR_GROUP_MEMBER,
		$errors = array()
	): string {
		$html_sections = [];

		// TODO: Handle is_read_only?

		if ( $show_name ) {
			$person_name_question = new FieldText( 'Vad heter du?' );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_NAME, @$errors[ self::FIELD_PERSON_NAME ] );
		}

		if ( $show_pno ) {
			$person_name_question = new FieldPno( 'Vad har du för födelsedag?', Strings::get( 'person.form.pno.hint' ) );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PNO, @$errors[ self::FIELD_PERSON_PNO ] );
		}

		if ( $show_email ) {
			$person_name_question = new FieldEmail( 'Vilken e-postadress har du?', Strings::get( 'person.form.email.hint' ) );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_EMAIL, @$errors[ self::FIELD_PERSON_EMAIL ] );
		}

		if ( $show_phone ) {
			$person_name_question = new FieldPhone( 'Vilket telefonnummer har du?', Strings::get( 'person.form.phone.hint' ) );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PHONE, @$errors[ self::FIELD_PERSON_PHONE ] );
		}

		if ( $show_food ) {
			$person_name_question = new FieldText( 'Matallergier och fikaönskemål', Strings::get( 'person.form.food.hint' ) );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_FOOD, @$errors[ self::FIELD_PERSON_FOOD ] );
		}

		$html_sections[] = $this->get_recaptcha_html();

		$html_sections[] = sprintf( '<div style="display: none;"><input type="hidden" name="%s" value="%s"></div>',
			self::FIELD_PERSON_ROLE,
			$role );

		return join( $html_sections );
	}

	private function get_submit_button_html() {
		return sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="%s">%s</button></div>', self::ACTION_BUTTON_NAME, self::ACTION_NAME_SAVE, 'Jag anmäler mig' );
	}

	private function create_person( Group $group ): Person {
		$person           = $this->init_posted_person();
		$person->group_id = $group->id;
		$person->set_as_regular_group_member();

		try {

			$category = $group->get_derived_group_category();
			$person->validate( $category->get_rule_set() );

			$new_person_id = $this->person_dao->create( $person );
			if ( $new_person_id !== false ) {
				$person = $this->person_dao->get( $new_person_id );

				return $person;
			} else {
				throw new Exception( 'Ett fel uppstod. Vi vet tyvärr inte riktigt varför.' );
			}
		} catch ( ValidationException $e ) {
			throw new ValidationException( self::FIELD_PREFIX_PERSON . $e->getField(), $e->getMessage() );
		}
	}

	private function send_person_welcome_mail( Person $person ) {
		$event_message_sender = new EventMessageSender();
		$event_message_sender->send_new_person_messages( $person );
	}
}