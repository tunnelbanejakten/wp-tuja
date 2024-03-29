<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\Frontend;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\util\rules\RuleEvaluationException;
use tuja\util\Strings;

// TODO: Unify error handling so that there is no mix of "arrays of error messages" and "exception throwing". Pick one practice, don't mix. Throwing exceptions might be preferable.
class GroupPeopleEditor extends AbstractGroupView {
	private $read_only;

	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Personer i %s' );

	}

	public static function params_section_description( GroupCategory $group_category ): array {
		$rules = $group_category->get_rules()->get_values();

		return array_merge( [
			'group_category_name' => $group_category->name
		], $rules );
	}

	function output() {

		Frontend::use_script( 'jquery' );
		Frontend::use_script( 'tuja-edit-group.js' );

		$group = $this->get_group();

		$this->check_group_not_on_waiting_list( $group );

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

		$forms = [];
		foreach ( Person::PERSON_TYPES as $type ) {
			list ( $min, $max ) = $category->get_rules()->get_people_count_range( $type );
			if ( $max > 0 ) {
				$people = array_filter( $this->get_people(), function ( Person $person ) use ( $type ) {
					return $person->get_type() === $type;
				} );

				$are_more_people_allowed = true; // count($people) < $max;
				$are_fewer_people_allowed = true; // count($people) > $min;

				$form = $this->get_form_people_html( $people,
					$errors,
					// Enable button to add more people if we haven't reached the upper limit yet
					$are_more_people_allowed,
					// Enable button to remove people only there is already enough people added
					$are_fewer_people_allowed,
					$type,
					Strings::get( 'group_people_editor.' . $type . '.add_button' ) );

				$forms[] = sprintf( '
					<h2>%s</h2>
				    <p><small>%s</small></p>
				    %s
					',
					Strings::get( 'group_people_editor.' . $type . '.header' ),
					Strings::get( 'group_people_editor.' . $type . '.description', self::params_section_description( $category ) ),
					$form );
			}
		}

		$form_save_button = $this->get_form_save_button_html();
		$home_link        = GroupHomeInitiator::link( $group );
		include( 'views/group-people-editor.php' );
	}

	function is_read_only(): bool {
		if ( ! isset( $this->read_only ) ) {
			$this->read_only = ! $this->is_edit_allowed( $this->get_group() );
		}

		return $this->read_only;
	}

	private function get_form_people_html(
		array $people,
		array $errors,
		bool $is_add_enabled,
		bool $is_remove_enabled,
		string $role,
		string $add_button_label = 'Ny person'
	) {
		$html_sections = [];

		if ( is_array( $people ) ) {
			$html_sections[] = sprintf( '<div class="tuja-people-existing">%s</div>',
				join( array_map( function ( Person $person ) use ( $errors, $is_remove_enabled ) {
					return $this->render_person_form( $person, $is_remove_enabled, $this->is_save_request() );
				}, $people ) ) );
		}

		if ( $is_add_enabled ) {
			$html_sections[] = sprintf( '<div class="tuja-item-buttons"><button type="button" value="%s" class="tuja-add-person">%s</button></div>', 'new_person', $add_button_label );
			$person_template = new Person();
			$person_template->set_type( $role );
			$html_sections[] = sprintf( '<div class="tuja-person-template">%s</div>', $this->render_person_form( $person_template, $is_remove_enabled, false ) );
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
			$person            = $this->init_posted_person( $id, $this->is_save_request() );
			$person->random_id = $id;

			return $person;
		}, $unsaved_ids );

		// Get all people, both saved and unsaved:
		$people = array_merge( $preexisting_people, $unsaved_people );

		return $people;
	}

	private function render_person_form(
		Person $person,
		bool $show_delete = true,
		bool $show_validation_errors = true
	): string {

		$read_only = $this->is_read_only();

		$html_sections = [
			$this->get_person_form()->render( $person, $show_validation_errors )
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

		$preexisting_ids = array_map(
			function ( $person ) {
			return $person->random_id;
			},
			$people
		);

		$submitted_ids = $this->get_submitted_person_ids();

		$updated_ids = array_intersect( $preexisting_ids, $submitted_ids );
		$deleted_ids = array_diff( $preexisting_ids, $submitted_ids );
		$created_ids = array_diff( $submitted_ids, $preexisting_ids );

		$update_requested = ! empty( $created_ids ) || ! empty( $updated_ids );
		$delete_requested = ! empty( $deleted_ids );

		if ( ! $this->is_edit_allowed( $group, $update_requested, $delete_requested ) ) {
			throw new RuleEvaluationException( 'Det går inte att ändra anmälan nu' );
		}

		// SAVE CHANGES

		foreach ( $created_ids as $id ) {
			try {
				$new_person           = $this->init_posted_person( $id, $this->is_save_request());
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

		$people_map = array_combine(
			array_map(
				function ( $person ) {
			return $person->random_id;
				},
				$people
			),
			$people
		);

		foreach ( $updated_ids as $id ) {
			if ( isset( $people_map[ $id ] ) ) {
				try {
					$is_person_property_updated = $this->get_person_form()->update_with_posted_values( $people_map[ $id ] );
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