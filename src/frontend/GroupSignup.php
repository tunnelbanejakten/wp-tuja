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
		$errors         = [];
		$errors_overall = '';
		$group          = $this->get_group();

		$this->check_group_status( $group );

		if ( ! $this->is_edit_allowed( $group ) ) {
			return sprintf( '<p class="tuja-message tuja-message-error">%s</p>', 'Tyvärr så går det inte att anmäla sig nu.' );
		}

		$do_save                     = $_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE;
		try {
			if ( $do_save ) {
				$this->validate_recaptcha_html();

				// TODO: It's a bit odd that create_group and delete_person throw exceptions whereas update_group returns an error of error messages.
				$new_person = $this->create_person();

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

		$form = $this->get_form_html( $do_save );

		$submit_button = $this->get_submit_button_html();

		include( 'views/group-signup.php' );
	}

	private function get_form_html( bool $show_validation_errors ): string {
		$html_sections = [
			( new PersonForm(
				false,
				false, // TODO: Handle is_read_only?
				$show_validation_errors,
				$this->get_group()->get_category()->get_rules()
			) )->render( $this->get_person() )
		];

		$html_sections[] = $this->get_recaptcha_html();

		return join( $html_sections );
	}

	private function get_submit_button_html() {
		return sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="%s">%s</button></div>', self::ACTION_BUTTON_NAME, self::ACTION_NAME_SAVE, 'Jag anmäler mig' );
	}

	private function create_person( ): Person {
		$person = $this->get_person();

		try {

			$category = $this->get_group()->get_category();
			$person->validate( $category->get_rules() );

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

	private function get_person(): Person {
		$person           = $this->init_posted_person();
		$person->group_id = $this->get_group()->id;
		$person->set_type(Person::PERSON_TYPE_REGULAR);

		return $person;
	}

	private function send_person_welcome_mail( Person $person ) {
		$event_message_sender = new EventMessageSender();
		$event_message_sender->send_new_person_messages( $person );
	}
}