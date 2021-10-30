<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;

class PersonEditor extends AbstractGroupView {
	private $person_key;

	public function __construct( $url, $group_key, $person_key ) {
		parent::__construct( $url, $group_key, 'Din anmälan' );
		$this->person_key = $person_key;
	}

	function output() {
		$person = $this->get_person();
		$group  = $this->get_group();

		$this->check_group_status( $group );

		$errors = [];
		if ( $person->group_id != $group->id ) {
			throw new Exception( 'Fel grupp.' );
		}

		$is_read_only = ! $this->is_edit_allowed( $group );

		$real_category = $group->get_category();

		$do_update = @$_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE;
		if ( $do_update ) {
			if ( ! $is_read_only ) {
				$errors = $this->update_person( $person );
				if ( empty( $errors ) ) {
					printf( '<p class="tuja-message tuja-message-success">%s</p>', 'Ändringarna har sparats. Tack.' );

					return;
				}
				$this->group_dao->run_registration_rules( $group );
			} else {
				$errors = [ '__' => 'Tyvärr så kan anmälningar inte ändras nu.' ];
			}
		}

		$errors_overall = isset( $errors['__'] ) ? sprintf( '<p class="tuja-message tuja-message-error">%s</p>', $errors['__'] ) : '';

		$form = ( new PersonForm(
			false,
			$is_read_only,
			false,
			$do_update,
			$real_category->get_rules()
		) )->render( $person );

		$submit_button = $this->get_submit_button_html( $is_read_only );

		include( 'views/person-editor.php' );
	}

	function get_person(): Person {
		$person = $this->person_dao->get_by_key( $this->person_key );
		if ( $person == false ) {
			throw new Exception( 'Oj, vi hittade inte personen' );
		}

		return $person;
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

	private function update_person( Person $person ) {
		$posted_values = [
			'name'  => @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_NAME, $person ) ],
			'email' => @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_EMAIL, $person ) ],
			'phone' => @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_PHONE, $person ) ],
			'pno'   => @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_PNO, $person ) ],
			'food'  => @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_FOOD, $person ) ],
			'note'  => @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_NOTE, $person ) ]
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