<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\util\rules\RuleEvaluationException;
use tuja\util\Strings;
use tuja\view\FieldEmail;
use tuja\view\FieldPhone;
use tuja\view\FieldPno;
use tuja\view\FieldText;

// TODO: Unify error handling so that there is no mix of "arrays of error messages" and "exception throwing". Pick one practice, don't mix. Throwing exceptions might be preferable.
class GroupPeopleEditor extends AbstractGroupView {
	private $read_only;

	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Personer i %s' );
	}

	function output() {

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'tuja-editgroup-script' ); // Needed?

		try {
			$group = $this->get_group();

			$this->check_group_status( $group );

			$category = $group->get_derived_group_category();
			$errors   = [];

			if ( @$_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE ) {
				try {
					$errors = $this->update_group( $group );
					if ( empty( $errors ) ) {
						printf( '<p class="tuja-message tuja-message-success">%s</p>', 'Ändringarna har sparats. Tack.' );
					}
					$this->group_dao->run_registration_rules( $group );
				} catch ( RuleEvaluationException $e ) {
					$errors = array( '__' => $e->getMessage() );
				}
			}

			$errors_overall = isset( $errors['__'] ) ? sprintf( '<p class="tuja-message tuja-message-error">%s</p>', $errors['__'] ) : '';

			$form_extra_contact = $this->get_form_extra_contact_html( $errors );
			if ( $category->get_rule_set()->is_adult_supervisor_required() ) {
				$form_adult_supervisor = $this->get_form_adult_supervisor_html( $errors );
			}
			list ( $group_size_min, $group_size_max ) = $category->get_rule_set()->get_group_size_range();
			$form_group_contact = $this->get_form_group_contact_html( $errors );
			$form_group_members = $this->get_form_group_members_html( $errors );
			$form_save_button   = $this->get_form_save_button_html();
			$home_link          = GroupHomeInitiator::link( $group );
			include( 'views/group-people-editor.php' );
		} catch ( Exception $e ) {
			print $this->get_exception_message_html( $e );
		}
	}

	function is_read_only(): bool {
		if ( ! isset( $this->read_only ) ) {
			$this->read_only = ! $this->is_edit_allowed( $this->get_group() );
		}

		return $this->read_only;
	}

	private function get_form_adult_supervisor_html( array $errors ) {
		$people = array_filter( $this->get_people(), function ( Person $person ) {
			return $person->is_adult_supervisor();
		} );

		return $this->get_form_people_html( $people,
			$errors,
			false,
			true,
			true,
			false,
			false,
			true,
			self::ROLE_ADULT_SUPERVISOR,
			'Lägg till vuxen' );
	}

	private function get_form_group_contact_html( array $errors ) {
		$people = array_filter( $this->get_people(), function ( Person $person ) {
			return $person->is_group_leader();
		} );

		return $this->get_form_people_html( $people,
			$errors,
			true,
			true,
			true,
			true,
			true,
			true,
			self::ROLE_GROUP_LEADER );
	}

	private function get_form_group_members_html( array $errors ) {
		$people = array_filter( $this->get_people(), function ( Person $person ) {
			return $person->is_regular_group_member();
		} );

		return $this->get_form_people_html(
			$people,
			$errors,
			false,
			false,
			false,
			true,
			true,
			true,
			self::ROLE_REGULAR_GROUP_MEMBER,
			'Lägg till deltagare' );
	}

	private function get_form_extra_contact_html( $errors ) {
		$people = array_filter( $this->get_people(), function ( Person $person ) {
			return $person->is_contact() && ! $person->is_attending();
		} );

		return $this->get_form_people_html( $people,
			$errors,
			false,
			true,
			false,
			false,
			false,
			false,
			self::ROLE_EXTRA_CONTACT,
			'Lägg till extra kontaktperson' );
	}

	private function get_form_people_html(
		array $people,
		array $errors,
		bool $is_fixed_list,
		bool $show_email,
		bool $show_phone,
		bool $show_pno,
		bool $show_name,
		bool $show_food,
		string $role,
		string $add_button_label = 'Ny person'
	) {
		$html_sections = [];

		if ( is_array( $people ) ) {
			$html_sections[] = sprintf( '<div class="tuja-people-existing">%s</div>',
				join( array_map( function ( $person ) use ( $errors, $is_fixed_list, $show_email, $show_phone, $show_pno, $show_name, $show_food, $role ) {
					return $this->render_person_form( $person, $show_name, $show_email, $show_phone, $show_pno, $show_food, ! $is_fixed_list, $role, $errors );
				}, $people ) ) );
		}

		if ( ! $is_fixed_list ) {
			$html_sections[] = sprintf( '<div class="tuja-item-buttons"><button type="button" value="%s" class="tuja-add-person">%s</button></div>', 'new_person', $add_button_label );
			$html_sections[] = sprintf( '<div class="tuja-person-template">%s</div>', $this->render_person_form( new Person(), $show_name, $show_email, $show_phone, $show_pno, $show_food, ! $is_fixed_list, $role, $errors ) );
		}

		return sprintf( '<div class="tuja-people tuja-person-role-%s">%s</div>', $role, join( $html_sections ) );
	}

	private function get_form_save_button_html() {
		$html_sections = [];

		if ( ! $this->is_read_only() ) {
			$html_sections[] = sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="%s">%s</button></div>',
				self::ACTION_BUTTON_NAME,
				self::ACTION_NAME_SAVE,
				'Spara' );
		} else {
			// TODO: Should other error messages also contain email link?
			$html_sections[] = sprintf( '<p class="tuja-message tuja-message-error">%s</p>',
				Strings::get( 'group_people_editor.read_only.message',
					sprintf( '<a href="mailto:%s">%s</a>', get_bloginfo( 'admin_email' ), get_bloginfo( 'admin_email' ) ) )
			);
		}

		return join( $html_sections );
	}

	private function get_people() {
		// Get people already saved in database:
		$preexisting_people = $this->get_current_group_members();
		$preexisting_ids    = array_map( function ( $person ) {
			return $person->random_id;
		}, $preexisting_people );

		// Get people who should have been saved in the database but which were not (probably because of input validation problems):
		$unsaved_ids = array_diff( $this->get_submitted_person_ids(), $preexisting_ids );
		sort( $unsaved_ids );
		$unsaved_people = array_map( function ( $id ) {
			$person            = $this->init_posted_person( $id );
			$person->random_id = $id;

			return $person;
		}, $unsaved_ids );

		// Get all people, both saved and unsaved:
		$people = array_merge( $preexisting_people, $unsaved_people );

		return $people;
	}

	// Move to AbstractGroupView?
	private function render_person_form(
		Person $person,
		bool $show_name = true,
		bool $show_email = true,
		bool $show_phone = true,
		bool $show_pno = true,
		bool $show_food = true,
		bool $show_delete = true,
		string $role = self::ROLE_REGULAR_GROUP_MEMBER,
		$errors = array()
	): string {

		$read_only = $this->is_read_only();

		$html_sections = [];

		// TODO: Handle $errors['__']?

		$random_id = $person->random_id ?: '';

		if ( $show_name ) {
			$person_name_question = new FieldText( 'Namn', null, $read_only, [], true );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_NAME . '__' . $random_id, @$errors[ $random_id . '__name' ], $person->name );
		}

		if ( $show_pno ) {
			$person_name_question = new FieldPno( 'Födelsedag och sånt (ååmmddnnnn)', Strings::get( 'person.form.pno.hint' ), $read_only, true );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PNO . '__' . $random_id, @$errors[ $random_id . '__pno' ], $person->pno );
		}

		if ( $show_email ) {
			$person_name_question = new FieldEmail( 'E-postadress', Strings::get( 'person.form.email.hint' ), $read_only, true );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_EMAIL . '__' . $random_id, @$errors[ $random_id . '__email' ], $person->email );
		}

		if ( $show_phone ) {
			$person_name_question = new FieldPhone( 'Telefonnummer', Strings::get( 'person.form.phone.hint' ), $read_only, true );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PHONE . '__' . $random_id, @$errors[ $random_id . '__phone' ], $person->phone );
		}

		if ( $show_food ) {
			$person_name_question = new FieldText( 'Matallergier och fikaönskemål', Strings::get( 'person.form.food.hint' ), $read_only, [], true );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_FOOD . '__' . $random_id, @$errors[ $random_id . '__food' ], $person->food );
		}

		$html_sections[] = sprintf( '<div style="display: none;"><input type="hidden" name="%s" value="%s"></div>',
			self::FIELD_PERSON_ROLE . '__' . $random_id,
			$role );

		if ( $show_delete && ! $read_only ) {
			$html_sections[] = sprintf( '<div class="tuja-item-buttons tuja-item-buttons-right"><button type="button" class="tuja-delete-person">%s</button></div>',
				'Ta bort' );
		}

		return sprintf( '<div class="tuja-signup-person">%s</div>', join( $html_sections ) );
	}

	private function update_group( Group $group ) {
		// INIT
		$validation_errors = array();
		$overall_success   = true;
		$group_id          = $group->id;
		$competition       = $this->competition_dao->get( $group->competition_id );

		$category = $group->get_derived_group_category();

		// DETERMINE REQUESTED CHANGES
		$people = $this->get_current_group_members();

		$preexisting_ids = array_map( function ( $person ) {
			return $person->random_id;
		}, $people );

		$submitted_ids = $this->get_submitted_person_ids();

		$updated_ids = array_intersect( $preexisting_ids, $submitted_ids );
		$deleted_ids = array_diff( $preexisting_ids, $submitted_ids );
		$created_ids = array_diff( $submitted_ids, $preexisting_ids );

		if ( ! $this->is_edit_allowed( $group ) ) {
			throw new RuleEvaluationException( 'Det går inte att ändra anmälan nu' );
		}
		$real_category = $group->get_derived_group_category();
		if ( isset( $real_category ) && ! empty( $deleted_ids ) ) {
			$delete_group_member_allowed = $real_category->get_rule_set()->is_delete_group_member_allowed( $competition );
			if ( ! $delete_group_member_allowed ) {
				throw new RuleEvaluationException( 'Det går inte att avanmäla från ' . $real_category->name );
			}
		}

		// SAVE CHANGES

		foreach ( $created_ids as $id ) {
			try {
				$new_person           = $this->init_posted_person( $id );
				$new_person->group_id = $group_id;

				$new_person->validate( $category->get_rule_set() );

				$new_person_id = $this->person_dao->create( $new_person );
				$this_success  = $new_person_id !== false;
				if ( $this_success ) {
					// Remove the POSTed data for the newly created person. This prevents the new person from being
					// printed twice when rendering the page after saving changes (the form for the person would have
					// been shown once when loading it from the database, since it is now an existing person, and once
					// when loading "people who should be created", since the incoming POSTed data still contains the
					// data entered by the user under the key $id (rather than the key generated during creation).
					foreach ( array_keys( $_POST ) as $key ) {
						if ( strpos( $key, $id ) !== false ) {
							unset( $_POST[ $key ] );
						}
					}
				}
				$overall_success = ( $overall_success and $this_success );
			} catch ( ValidationException $e ) {
				$validation_errors[ $id . '__' . $e->getField() ] = $e->getMessage();
				$overall_success                                  = false;
			} catch ( Exception $e ) {
				$overall_success = false;
			}
		}

		$people_map = array_combine( array_map( function ( $person ) {
			return $person->random_id;
		}, $people ), $people );

		foreach ( $updated_ids as $id ) {
			if ( isset( $people_map[ $id ] ) ) {
				try {
					$posted_values = [
						'name'  => $_POST[ self::FIELD_PERSON_NAME . '__' . $id ] ?: $_POST[ self::FIELD_PERSON_EMAIL . '__' . $id ],
						'email' => $_POST[ self::FIELD_PERSON_EMAIL . '__' . $id ],
						'phone' => $_POST[ self::FIELD_PERSON_PHONE . '__' . $id ],
						'pno'   => $_POST[ self::FIELD_PERSON_PNO . '__' . $id ],
						'food'  => $_POST[ self::FIELD_PERSON_FOOD . '__' . $id ]
					];

					$is_person_property_updated = false;
					foreach ( $posted_values as $prop => $new_value ) {
						if ( $people_map[ $id ]->{$prop} != $new_value ) {
							$people_map[ $id ]->{$prop} = $new_value;

							$is_person_property_updated = true;
						}
					}

					if ( $is_person_property_updated ) {
						$people_map[ $id ]->validate( $category->get_rule_set() );
						$affected_rows   = $this->person_dao->update( $people_map[ $id ] );
						$this_success    = $affected_rows !== false;
						$overall_success = ( $overall_success and $this_success );
					}
				} catch ( ValidationException $e ) {
					$validation_errors[ $id . '__' . $e->getField() ] = $e->getMessage();
					$overall_success                                  = false;
				} catch ( Exception $e ) {
					$overall_success = false;
				}
			}
		}

		foreach ( $deleted_ids as $id ) {
			if ( isset( $people_map[ $id ] ) ) {
				$delete_successful = $this->person_dao->delete_by_key( $id );
				if ( ! $delete_successful ) {
					$overall_success = false;
				}
			}
		}

		if ( ! $overall_success ) {
			$validation_errors['__'] = 'Alla ändringar kunde inte sparas.';
		}

		return $validation_errors;
	}

	private function get_submitted_person_ids(): array {
		// $person_prop_field_names are the keys in $_POST which correspond to form values for the group members.
		$person_prop_field_names = array_filter( array_keys( $_POST ), function ( $key ) {
			return substr( $key, 0, strlen( self::FIELD_PREFIX_PERSON ) ) === self::FIELD_PREFIX_PERSON;
		} );

		// $all_ids will include duplicates (one for each of the name, email and phone fields).
		// $all_ids will include empty strings because of the fields in the hidden template for new participant are submitted.
		$all_ids = array_map( function ( $key ) {
			list( , , $id ) = explode( '__', $key );

			return $id;
		}, $person_prop_field_names );

		return array_filter( array_unique( $all_ids ) /* No callback to outer array_filter means that empty strings will be skipped.*/ );
	}

	private function get_current_group_members(): array {
		return $this->person_dao->get_all_in_group( $this->get_group()->id );
	}
}