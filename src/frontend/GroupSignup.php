<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\data\store\GroupDao;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\frontend\router\PersonEditorInitiator;
use tuja\util\messaging\EventMessageSender;
use tuja\util\Recaptcha;
use tuja\view\CreatePersonShortcode;
use tuja\view\EditGroupShortcode;
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
			$group = $this->get_group();
			$form  = $this->render_form();
			include( 'views/group-signup.php' );
		} catch ( Exception $e ) {
			printf( '<p class="tuja-message tuja-message-error">%s</p>', $e->getMessage() );
		}
	}

	public function render_form(): String {
		$group = $this->get_group();

		if ( ! $this->is_edit_allowed( $group ) ) {
			return sprintf( '<p class="tuja-message tuja-message-error">%s</p>', 'Tyvärr så går det inte att anmäla sig nu.' );
		}

		if ( $_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE ) {
			try {
				$recaptcha_secret = get_option( 'tuja_recaptcha_sitesecret' );
				if ( ! empty( $recaptcha_secret ) ) {
					$recaptcha = new Recaptcha( $recaptcha_secret );
					$recaptcha->verify( $_POST['g-recaptcha-response'] );
				}

				// TODO: It's a bit odd that create_group and delete_person throw exceptions whereas update_group returns an arror of error messages.
				$new_person = $this->create_person( $group );

				$edit_link = PersonEditorInitiator::link( $group, $new_person );

				$this->send_person_welcome_mail( $new_person );

				if ( ! empty( $edit_link ) ) {
					return sprintf( '<p class="tuja-message tuja-message-success">Tack för din anmälan. Gå till <a href="%s">%s</a> om du behöver ändra din anmälan senare. Vi har också skickat länken till din e-postadress.</p>', $edit_link, $edit_link );
				} else {
					return sprintf( '<p class="tuja-message tuja-message-success">Tack för din anmälan.</p>' );
				}
			} catch ( ValidationException $e ) {
				return $this->render_create_form( $group, array( $e->getField() => $e->getMessage() ) );
			} catch ( Exception $e ) {
				// TODO: Create helper method for generating field names based on "group or person" and attribute name.
				return $this->render_create_form( $group, array( '__' => $e->getMessage() ) );
			}
		} else {
			return $this->render_create_form( $group );
		}
	}

	private function render_create_form(Group $group, $errors = array()): string
	{
		$html_sections = [];

		if (isset($errors['__'])) {
			$html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors['__']);
		}

		$person_name_question = new FieldText( 'Vad heter du?' );
		$html_sections[] = $this->render_field( $person_name_question, self::FIELD_PERSON_NAME, $errors[ self::FIELD_PERSON_NAME ] );

		$person_name_question = new FieldPno( 'Vad har du för födelsedag/personnummer?', 'Vi rekommenderar att du fyller i fullständigt personnummer.' );
		$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PNO, $errors[ self::FIELD_PERSON_PNO ] );

		$person_name_question = new FieldEmail( 'Vilken e-postadress har du?', 'Obligatoriskt för lagledare och funktionärer, rekommenderat för övriga.' );
		$html_sections[] = $this->render_field( $person_name_question, self::FIELD_PERSON_EMAIL, $errors[ self::FIELD_PERSON_EMAIL ] );

		$person_name_question = new FieldPhone( 'Vilket telefonnummer har du?', 'Obligatoriskt för lagledare och funktionärer, rekommenderat för övriga.' );
		$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PHONE, $errors[ self::FIELD_PERSON_PHONE ] );

		$person_name_question = new FieldText( 'Allergier och matönskemål', 'Arrangemanget är köttfritt och nötfritt. Fyll i här om du har ytterligare behov.' );
		$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_FOOD, $errors[ self::FIELD_PERSON_FOOD ] );

		$recaptcha_sitekey = get_option( 'tuja_recaptcha_sitekey' );
		if (!empty($recaptcha_sitekey)) {
			wp_enqueue_script('tuja-recaptcha-script');
			$html_sections[] = sprintf('<div class="tuja-robot-check"><div class="g-recaptcha" data-sitekey="%s"></div></div>', $recaptcha_sitekey);
		}

		$html_sections[] = sprintf('<div class="tuja-buttons"><button type="submit" name="%s" value="%s">%s</button></div>', self::ACTION_BUTTON_NAME, self::ACTION_NAME_SAVE, 'Jag anmäler mig');

		return sprintf('<form method="post">%s</form>', join($html_sections));
	}

	private function create_person($group): Person
	{
		$new_person = new Person();
		$new_person->group_id = $group->id;
		$new_person->name = $_POST[self::FIELD_PERSON_NAME];
		$new_person->email = $_POST[self::FIELD_PERSON_EMAIL];
		$new_person->phone = $_POST[self::FIELD_PERSON_PHONE];
		$new_person->pno = $_POST[self::FIELD_PERSON_PNO];
		$new_person->food = $_POST[self::FIELD_PERSON_FOOD];
		$new_person->is_group_contact = false;
		$new_person->is_competing = true;
		try {
			$new_person_id = $this->person_dao->create($new_person);
			if ($new_person_id !== false) {
				$new_person = $this->person_dao->get($new_person_id);
				return $new_person;
			} else {
				throw new Exception('Ett fel uppstod. Vi vet tyvärr inte riktigt varför.');
			}
		} catch (ValidationException $e) {
			throw new ValidationException(self::FIELD_PREFIX_PERSON . $e->getField(), $e->getMessage());
		}
	}

	private function send_person_welcome_mail( Person $person )
	{
		$event_message_sender = new EventMessageSender();
		$event_message_sender->send_new_person_messages( $person );
	}
}