<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\Frontend;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\util\rules\GroupCategoryRules;
use tuja\util\rules\RuleEvaluationException;
use tuja\util\Strings;
use tuja\view\FieldEmail;
use tuja\view\FieldPhone;
use tuja\view\FieldPno;
use tuja\view\FieldText;

// TODO: Unify error handling so that there is no mix of "arrays of error messages" and "exception throwing". Pick one practice, don't mix. Throwing exceptions might be preferable.
class GroupPeopleEditor extends AbstractGroupView {
	private $read_only;
	private $person_form;

	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Personer i %s' );

	}

	private function get_person_form(): PersonForm {
		if ( ! isset( $this->person_form ) ) {
			$this->person_form = new PersonForm(
				false,
				false,
				$this->is_save_request(),
				$this->get_group()->get_category()->get_rules()
			);
		}

		return $this->person_form;
	}

	function output() {

		Frontend::use_script( 'jquery' );
		Frontend::use_script( 'tuja-edit-group.js' );

		$group = $this->get_group();

		$this->check_group_status( $group );

		$category = $group->get_category();
		$errors   = [];

		if ( $this->is_save_request() ) {
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
		if ( $category->get_rules()->is_adult_supervisor_required() ) {
			$form_adult_supervisor = $this->get_form_adult_supervisor_html( $errors );
		}
		list ( $group_size_min, $group_size_max ) = $category->get_rules()->get_people_count_range( Person::PERSON_TYPE_LEADER, Person::PERSON_TYPE_REGULAR );
		$form_group_contact = $this->get_form_group_contact_html( $errors );
		$form_group_members = $this->get_form_group_members_html( $errors );
		$form_save_button   = $this->get_form_save_button_html();
		$home_link          = GroupHomeInitiator::link( $group );
		include( 'views/group-people-editor.php' );
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
			self::ROLE_REGULAR_GROUP_MEMBER,
			'Lägg till deltagare' );
	}

	private function get_form_extra_contact_html( array $errors ) {
		$people = array_filter( $this->get_people(), function ( Person $person ) {
			return $person->is_contact() && ! $person->is_attending();
		} );

		return $this->get_form_people_html( $people,
			$errors,
			false,
			self::ROLE_EXTRA_CONTACT,
			'Lägg till extra kontaktperson' );
	}

	private function get_form_people_html(
		array $people,
		array $errors,
		bool $is_fixed_list,
		string $role,
		string $add_button_label = 'Ny person'
	) {
		$html_sections = [];

		if ( is_array( $people ) ) {
			$html_sections[] = sprintf( '<div class="tuja-people-existing">%s</div>',
				join( array_map( function ( Person $person ) use ( $errors, $is_fixed_list ) {
					return $this->render_person_form( $person, ! $is_fixed_list );
				}, $people ) ) );
		}

		if ( ! $is_fixed_list ) {
			$html_sections[] = sprintf( '<div class="tuja-item-buttons"><button type="button" value="%s" class="tuja-add-person">%s</button></div>', 'new_person', $add_button_label );
			$person_template = new Person();
			$person_template->set_type( $role );
			$html_sections[] = sprintf( '<div class="tuja-person-template">%s</div>', $this->render_person_form( $person_template, ! $is_fixed_list ) );
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
		bool $show_delete = true
	): string {

		$read_only = $this->is_read_only();

		$html_sections = [
			$this->get_person_form()->render( $person )
		];

		// TODO: Handle $errors['__']?

		$random_id = $person->random_id ?: '';

		$html_sections[] = sprintf( '<div style="display: none;"><input type="hidden" name="%s" value="%s"></div>',
			self::FIELD_PERSON_ROLE . '__' . $random_id,
			$person->get_type() );

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

		$category = $group->get_category();

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
		$real_category = $group->get_category();
		if ( isset( $real_category ) && ! empty( $deleted_ids ) ) {
			$delete_group_member_allowed = $real_category->get_rules()->is_delete_group_member_allowed();
			if ( ! $delete_group_member_allowed ) {
				throw new RuleEvaluationException( 'Det går inte att avanmäla från ' . $real_category->name );
			}
		}

		// SAVE CHANGES

		foreach ( $created_ids as $id ) {
			try {
				$new_person           = $this->init_posted_person( $id );
				$new_person->group_id = $group_id;

				$new_person->validate( $category->get_rules() );

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
						'name'  => $_POST[ PersonForm::get_field_name( PersonForm::FIELD_NAME, $people_map[ $id ] ) ] ?: $_POST[ PersonForm::get_field_name( PersonForm::FIELD_EMAIL, $people_map[ $id ] ) ],
						'email' => $_POST[ PersonForm::get_field_name( PersonForm::FIELD_EMAIL, $people_map[ $id ] ) ],
						'phone' => $_POST[ PersonForm::get_field_name( PersonForm::FIELD_PHONE, $people_map[ $id ] ) ],
						'pno'   => $_POST[ PersonForm::get_field_name( PersonForm::FIELD_PNO, $people_map[ $id ] ) ],
						'food'  => $_POST[ PersonForm::get_field_name( PersonForm::FIELD_FOOD, $people_map[ $id ] ) ]
					];

					$is_person_property_updated = false;
					foreach ( $posted_values as $prop => $new_value ) {
						if ( $people_map[ $id ]->{$prop} != $new_value ) {
							$people_map[ $id ]->{$prop} = $new_value;

							$is_person_property_updated = true;
						}
					}

					if ( $is_person_property_updated ) {
						$people_map[ $id ]->validate( $category->get_rules() );
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

	private function is_save_request(): bool {
		return @$_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE;
	}
}