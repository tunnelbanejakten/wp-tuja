<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\view\FieldEmail;
use tuja\view\FieldPhone;
use tuja\view\FieldPno;
use tuja\view\FieldText;

class PersonEditor extends AbstractGroupView {
	private $person_key;

	public function __construct( $url, $group_key, $person_key ) {
		parent::__construct( $url, $group_key, 'Din anmälan' );
		$this->person_key = $person_key;
	}

	function output() {
		try {
			$person = $this->get_person();
			$group  = $this->get_group();
			$errors = [];
			if ( $person->group_id != $group->id ) {
				throw new Exception( 'Fel grupp.' );
			}

			$is_read_only = ! $this->is_edit_allowed( $group );

			$real_category               = $group->get_derived_group_category();
			$collect_contact_information = $real_category->get_rule_set()->is_contact_information_required_for_regular_group_member();
			$collect_ssn                 = $real_category->get_rule_set()->is_ssn_required();

			if ( @$_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE ) {
				if ( ! $is_read_only ) {
					$errors = $this->update_person( $person );
					if ( empty( $errors ) ) {
						printf( '<p class="tuja-message tuja-message-success">%s</p>', 'Ändringarna har sparats. Tack.' );

						return;
					}
				} else {
					$errors = [ '__' => 'Tyvärr så kan anmälningar inte ändras nu.' ];
				}
			}

			$errors_overall = isset( $errors['__'] ) ? sprintf( '<p class="tuja-message tuja-message-error">%s</p>', $errors['__'] ) : '';

			$form = $this->get_form_html( $person, true, $collect_contact_information, $collect_contact_information, $collect_ssn, true, $errors, $is_read_only );

			$submit_button = $this->get_submit_button_html( $is_read_only );

			include( 'views/person-editor.php' );
		} catch ( Exception $e ) {
			printf( '<p class="tuja-message tuja-message-error">%s</p>', $e->getMessage() );
		}
	}

	function get_person(): Person {
		$person = $this->person_dao->get_by_key( $this->person_key );
		if ( $person == false ) {
			throw new Exception( 'Oj, vi hittade inte personen' );
		}

		return $person;
	}

	// TODO: Similar function in three classes. Move to AbstractGroupView?
	private function get_form_html(
		$person,
		bool $show_name = true,
		bool $show_email = true,
		bool $show_phone = true,
		bool $show_pno = true,
		bool $show_food = true,
		$errors = array(),
		$read_only = false
	): string {
		$html_sections = [];

		// TODO: Handle $read_only in all fields?

		if ( $show_name ) {
			$person_name_question = new FieldText( 'Namn', null, $read_only );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_NAME, @$errors['name'], $person->name );
		}

		if ( $show_pno ) {
			$person_name_question = new FieldPno( 'Födelsedag och sånt', 'Vi rekommenderar att du fyller i fullständigt personnummer.', $read_only );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PNO, @$errors['pno'], $person->pno );
		}

		if ( $show_email ) {
			$person_name_question = new FieldEmail( 'E-postadress' );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_EMAIL, @$errors['email'], $person->email );
		}

		if ( $show_phone ) {
			$person_name_question = new FieldPhone( 'Telefonnummer' );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PHONE, @$errors['phone'], $person->phone );
		}

		if ( $show_food ) {
			$person_name_question = new FieldText( 'Allergier och matönskemål', 'Arrangemanget är köttfritt och nötfritt. Fyll i här om du har ytterligare behov.', $read_only );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_FOOD, @$errors['food'], $person->food );
		}

		return join( $html_sections );
	}

	private function get_submit_button_html( $read_only = false ) {
		if ( ! $read_only ) {
			return sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="%s">%s</button></div>',
				self::ACTION_BUTTON_NAME,
				self::ACTION_NAME_SAVE,
				'Spara' );
		} else {
			return sprintf( '<p class="tuja-message tuja-message-error">%s</p>',
				sprintf( 'Du kan inte längre ändra din anmälan. Kontakta <a href="mailto:%s">%s</a> om du behöver ändra något.',
					get_bloginfo( 'admin_email' ),
					get_bloginfo( 'admin_email' ) ) );
		}
	}

	private function update_person( $person ) {
		$posted_values = [
			// TODO: DRY?
			'name'  => $_POST[ self::FIELD_PERSON_NAME ],
			'email' => $_POST[ self::FIELD_PERSON_EMAIL ],
			'phone' => $_POST[ self::FIELD_PERSON_PHONE ],
			'pno'   => $_POST[ self::FIELD_PERSON_PNO ],
			'food'  => $_POST[ self::FIELD_PERSON_FOOD ]
		];

		$is_updated = false;
		foreach ( $posted_values as $prop => $new_value ) {
			if ( $person->{$prop} != $new_value ) {
				$person->{$prop} = $new_value;

				$is_updated = true;
			}
		}

		$validation_errors = array();
		if ( $is_updated ) {
			$success = false;
			try {
				$affected_rows = $this->person_dao->update( $person );
				if ( $affected_rows !== false ) {
					$success = true;
				}
			} catch ( ValidationException $e ) {
				$validation_errors[ $e->getField() ] = $e->getMessage();
			} catch ( Exception $e ) {
			}

			if ( ! $success ) {
				$validation_errors['__'] = 'Alla ändringar kunde inte sparas.';
			}
		}

		return $validation_errors;
	}
}