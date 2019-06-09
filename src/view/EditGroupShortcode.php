<?php

namespace tuja\view;

use tuja\data\model\ValidationException;
use Exception;
use tuja\data\model\Person;
use tuja\data\model\Question;


// TODO: Unify error handling so that there is no mix of "arrays of error messages" and "exception throwing". Pick one practice, don't mix. Throwing exceptions might be preferable.
class EditGroupShortcode extends AbstractGroupShortcode
{
    const ACTION_NAME_DELETE_PERSON_PREFIX = 'delete_person__';

	const ROLE_ISCONTACT_LABEL = 'Är kontaktperson och lagledare';
	const ROLE_ISNOTCOMPETING_LABEL = 'Är inte med och tävlar utan följer bara med';


	private $group_key;
	private $enable_group_category_selection;

	public function __construct(
		$wpdb,
		$group_key,
		$is_crew_form,
		$enable_group_category_selection = true
	)
    {
        parent::__construct($wpdb, $is_crew_form);
	    $this->group_key                       = $group_key;
	    $this->enable_group_category_selection = $enable_group_category_selection;
    }

    public function render(): String
    {
        $group_key = $this->group_key;

        if (isset($group_key)) {
            $group = $this->group_dao->get_by_key($group_key);
            if ($group === false) {
                return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Oj, vi vet inte vilket lag du är med i.');
            }

            $is_read_only = !$this->is_edit_allowed($group->competition_id);
            $errors = array();

            if ($_POST[self::ACTION_BUTTON_NAME] == self::ACTION_NAME_SAVE) {
                if (!$is_read_only) {
                    $errors = $this->update_group($group);
                } else {
                    $errors = array('__' => 'Tyvärr så kan anmälningar inte ändras nu.');
                }
            }
            return $this->render_update_form($group, $errors, $is_read_only);
        } else {
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Oj, vi vet inte vilket lag du är med i.');
        }
    }

    private function render_update_form($group, $errors = array(), $read_only = false): string
    {
        wp_enqueue_script('tuja-editgroup-script');

	    // Get people already saved in database:
	    $preexisting_people = $this->person_dao->get_all_in_group( $group->id );
	    $preexisting_ids = array_map( function ( $person ) {
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

	    $html_sections = [];

        if (isset($errors['__'])) {
            $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors['__']);
        }

        $html_sections[] = sprintf('<h3>Laget</h3>');

	    $group_name_question = new FieldText( 'Vad heter ert lag?', null, $read_only );
	    $html_sections[]     = $this->render_field( $group_name_question, self::FIELD_GROUP_NAME, $errors['name'], $group->name );

	    if ( $this->enable_group_category_selection ) {
		    $categories = $this->get_categories( $group->competition_id );

		    $group_category = $this->get_group_category( $group );

		    $current_group_category_name = reset( array_filter( $categories, function ( $category ) use ( $group_category ) {
			    return isset( $group_category ) && $group_category->id == $category->id;
		    } ) )->name;

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
					    'Välj den som de flesta av deltagarna tillhör.',
					    false,
					    $group_category_options,
					    false );
				    $html_sections[]         = $this->render_field( $group_category_question, self::FIELD_GROUP_AGE, $errors['age'], $current_group_category_name );
				    break;
		    }
	    }

        $html_sections[] = sprintf('<h3>Deltagarna</h3>');

        if (is_array($people)) {
	        $html_sections[] = sprintf( '<div class="tuja-people-existing">%s</div>', join( array_map( function ( $person ) use ( $errors, $read_only, $group ) {
		        return $this->render_person_form( $person, $errors, $read_only );
            }, $people)));
        }
        if (!$read_only) {
            $html_sections[] = sprintf('<div class="tuja-item-buttons"><button type="button" name="%s" value="%s" class="tuja-add-person">%s</button></div>', self::ACTION_BUTTON_NAME, 'new_person', 'Lägg till deltagare');
	        $html_sections[] = sprintf( '<div class="tuja-person-template">%s</div>', $this->render_person_form( new Person(), $errors, $read_only ) );
        }

        if (!$read_only) {
            $html_sections[] = sprintf('<div class="tuja-buttons"><button type="submit" name="%s" value="%s">%s</button></div>',
                self::ACTION_BUTTON_NAME,
                self::ACTION_NAME_SAVE,
                'Spara');
        } else {
            // TODO: Should other error messages also contain email link?
            $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>',
                sprintf('Du kan inte längre ändra er anmälan. Kontakta <a href="mailto:%s">%s</a> om du behöver ändra något.',
                    get_bloginfo('admin_email'),
                    get_bloginfo('admin_email')));
        }

        return sprintf('<form method="post">%s</form>', join($html_sections));
    }

	private function render_person_form( $person, $errors = array(), $read_only = false ): string
    {
        $html_sections = [];

        $random_id = $person->random_id ?: '';

	    $person_name_question = new FieldText( 'Namn', null, $read_only );
	    $html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_NAME . '__' . $random_id, $errors[ $random_id . '__name' ], $person->name );

	    $person_name_question = new FieldPno( 'Födelsedag och sånt', 'Vi rekommenderar alla att fylla in fullständigt personnummer.', $read_only );
	    $html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PNO . '__' . $random_id, $errors[ $random_id . '__pno' ], $person->pno );

	    $answer                = [
		    ! $person->is_competing ? self::ROLE_ISNOTCOMPETING_LABEL : null,
		    $person->is_group_contact ? self::ROLE_ISCONTACT_LABEL : null
	    ];
	    $person_roles_question = new FieldChoices( 'Har den här personen någon speciell uppgift?',
		    null,
		    false,
		    [
			    self::ROLE_ISCONTACT_LABEL,
			    self::ROLE_ISNOTCOMPETING_LABEL
		    ],
		    true );
	    $html_sections[]       = $this->render_field( $person_roles_question, self::FIELD_PERSON_ROLES . '__' . $random_id, $errors[ $random_id . '__roles' ], $answer );

	    $person_name_question = new FieldEmail( 'E-postadress', 'Obligatoriskt för lagledaren, rekommenderat för övriga.' );
	    $html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_EMAIL . '__' . $random_id, $errors[ $random_id . '__email' ], $person->email );

	    $person_name_question = new FieldPhone( 'Telefonnummer', 'Obligatoriskt för lagledaren, rekommenderat för övriga.' );
	    $html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PHONE . '__' . $random_id, $errors[ $random_id . '__phone' ], $person->phone );

	    $person_name_question = new FieldText( 'Allergier och matönskemål', 'Arrangemanget är köttfritt och nötfritt. Fyll i här om du har ytterligare behov.', $read_only );
	    $html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_FOOD . '__' . $random_id, $errors[ $random_id . '__food' ], $person->food );

	    if ( ! $read_only ) {
            $html_sections[] = sprintf('<div class="tuja-item-buttons"><button type="button" name="%s" value="%s%s" class="tuja-delete-person">%s</button></div>',
                self::ACTION_BUTTON_NAME,
                self::ACTION_NAME_DELETE_PERSON_PREFIX,
                $random_id,
	            'Ta bort deltagare' );
        }

        return sprintf('<div class="tuja-signup-person">%s</div>', join($html_sections));
    }

    private function update_group($group)
    {
        $validation_errors = array();
        $overall_success = true;

        $group_id = $group->id;

        $group->name = $_POST[self::FIELD_GROUP_NAME];
	    if ( $this->enable_group_category_selection ) {
		    $selected_category = $_POST[self::FIELD_GROUP_AGE];
		    $categories        = $this->get_categories($group->competition_id);
		    $found_category    = array_filter($categories, function ($category) use ($selected_category) {
			    return $category->name == $selected_category;
		    });
		    if ( count($found_category) == 1) {
			    $group->category_id = reset($found_category)->id;
		    }
	    }

        try {
            $affected_rows = $this->group_dao->update($group);
            if ($affected_rows === false) {
                $overall_success = false;
            }
        } catch (ValidationException $e) {
            $validation_errors[$e->getField()] = $e->getMessage();
            $overall_success = false;
        } catch (Exception $e) {
            $overall_success = false;
        }

        $people = $this->person_dao->get_all_in_group($group_id);

        $preexisting_ids = array_map(function ($person) {
            return $person->random_id;
        }, $people);

        $submitted_ids = $this->get_submitted_person_ids();

        $updated_ids = array_intersect($preexisting_ids, $submitted_ids);
        $deleted_ids = array_diff($preexisting_ids, $submitted_ids);
        $created_ids = array_diff($submitted_ids, $preexisting_ids);

        $people_map = array_combine(array_map(function ($person) {
            return $person->random_id;
        }, $people), $people);

        foreach ($created_ids as $id) {
            try {
	            $new_person           = $this->init_posted_person( $id );
	            $new_person->group_id = $group_id;

                $new_person_id = $this->person_dao->create($new_person);
                $this_success = $new_person_id !== false;
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
                $overall_success = ($overall_success and $this_success);
            } catch (ValidationException $e) {
	            $validation_errors[ $id . '__' . $e->getField() ] = $e->getMessage();
	            $overall_success                                  = false;
            } catch (Exception $e) {
                $overall_success = false;
            }
        }

        foreach ($updated_ids as $id) {
            if (isset($people_map[$id])) {
                try {
	                $is_competing = ! ( is_array( $_POST[ self::FIELD_PREFIX_PERSON . 'roles__' . $id ] )
	                                    && in_array( self::ROLE_ISNOTCOMPETING_LABEL, $_POST[ self::FIELD_PREFIX_PERSON . 'roles__' . $id ] ) );

	                $is_group_contact = is_array( $_POST[ self::FIELD_PREFIX_PERSON . 'roles__' . $id ] )
	                                    && in_array( self::ROLE_ISCONTACT_LABEL, $_POST[ self::FIELD_PREFIX_PERSON . 'roles__' . $id ] );

	                $people_map[ $id ]->name             = $_POST[ self::FIELD_PREFIX_PERSON . 'name__' . $id ];
	                $people_map[ $id ]->email            = $_POST[ self::FIELD_PREFIX_PERSON . 'email__' . $id ];
	                $people_map[ $id ]->phone            = $_POST[ self::FIELD_PREFIX_PERSON . 'phone__' . $id ];
	                $people_map[ $id ]->pno              = $_POST[ self::FIELD_PREFIX_PERSON . 'pno__' . $id ];
	                $people_map[ $id ]->food             = $_POST[ self::FIELD_PREFIX_PERSON . 'food__' . $id ];
	                $people_map[ $id ]->is_competing     = $is_competing;
	                $people_map[ $id ]->is_group_contact = $is_group_contact;

                    $affected_rows = $this->person_dao->update($people_map[$id]);
                    $this_success = $affected_rows !== false;
                    $overall_success = ($overall_success and $this_success);
                } catch (ValidationException $e) {
                    $validation_errors[$id . '__' . $e->getField()] = $e->getMessage();
                    $overall_success = false;
                } catch (Exception $e) {
                    $overall_success = false;
                }
            }
        }

        foreach ($deleted_ids as $id) {
            if (isset($people_map[$id])) {
                $delete_successful = $this->person_dao->delete_by_key($id);
                if (!$delete_successful) {
                    $overall_success = false;
                }
            }
        }

        if (!$overall_success) {
            $validation_errors['__'] = 'Alla ändringar kunde inte sparas.';
        }
        return $validation_errors;
    }

	private function init_posted_person( $id ) {
		$is_competing = ! ( is_array( $_POST[ self::FIELD_PREFIX_PERSON . 'roles__' . $id ] )
		                    && in_array( self::ROLE_ISNOTCOMPETING_LABEL, $_POST[ self::FIELD_PREFIX_PERSON . 'roles__' . $id ] ) );

		$is_group_contact = is_array( $_POST[ self::FIELD_PREFIX_PERSON . 'roles__' . $id ] )
		                    && in_array( self::ROLE_ISCONTACT_LABEL, $_POST[ self::FIELD_PREFIX_PERSON . 'roles__' . $id ] );

		$person                   = new Person();
		$person->name             = $_POST[ self::FIELD_PREFIX_PERSON . 'name__' . $id ];
		$person->email            = $_POST[ self::FIELD_PREFIX_PERSON . 'email__' . $id ];
		$person->phone            = $_POST[ self::FIELD_PREFIX_PERSON . 'phone__' . $id ];
		$person->pno              = $_POST[ self::FIELD_PREFIX_PERSON . 'pno__' . $id ];
		$person->food             = $_POST[ self::FIELD_PREFIX_PERSON . 'food__' . $id ];
		$person->is_competing     = $is_competing;
		$person->is_group_contact = $is_group_contact;

		return $person;
	}

	private function get_submitted_person_ids(): array
    {
        // $person_prop_field_names are the keys in $_POST which correspond to form values for the group members.
        $person_prop_field_names = array_filter(array_keys($_POST), function ($key) {
            return substr($key, 0, strlen(self::FIELD_PREFIX_PERSON)) === self::FIELD_PREFIX_PERSON;
        });

        // $all_ids will include duplicates (one for each of the name, email and phone fields).
        // $all_ids will include empty strings because of the fields in the hidden template for new participant are submitted.
        $all_ids = array_map(function ($key) {
            list(, , $id) = explode('__', $key);
            return $id;
        }, $person_prop_field_names);
        return array_filter(array_unique($all_ids) /* No callback to outer array_filter means that empty strings will be skipped.*/);
    }
}