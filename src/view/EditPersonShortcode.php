<?php

namespace tuja\view;

use tuja\data\model\ValidationException;
use Exception;


// TODO: Unify error handling so that there is no mix of "arrays of error messages" and "exception throwing". Pick one practice, don't mix.
class EditPersonShortcode extends AbstractGroupShortcode
{
    private $person_key;

    public function __construct($wpdb, $person_key)
    {
        parent::__construct($wpdb, false);
        $this->person_key = $person_key;
    }

    public function render(): String
    {
        $person_key = $this->person_key;

        if (isset($person_key)) {
            $person = $this->person_dao->get_by_key($person_key);
            if ($person === false) {
                return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Ingen person angiven.');
            }

            $group        = $this->group_dao->get($person->group_id);
	        $is_read_only = ! $this->is_edit_allowed( $group );

            if (@$_POST[self::ACTION_BUTTON_NAME] == self::ACTION_NAME_SAVE) {
                if (!$is_read_only) {
                    $errors = $this->update_person($person);
	                if ( empty( $errors ) ) {
		                printf( '<p class="tuja-message tuja-message-success">%s</p>', 'Ändringarna har sparats. Tack.' );
	                }
                } else {
                    $errors = array('__' => 'Tyvärr så kan anmälningar inte ändras nu.');
                }
                return $this->render_update_form($person, $errors, $is_read_only);
            } else {
                return $this->render_update_form($person, array(), $is_read_only);
            }
        } else {
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Ingen person angiven.');
        }
    }

    private function render_update_form($person, $errors = array(), $read_only = false): string
    {
        $html_sections = [];

        if (isset($errors['__'])) {
            $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', @$errors['__']);
        }

	    $person_name_question = new FieldText( 'Namn', null, $read_only );
	    $html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_NAME, @$errors['name'], $person->name );

	    $person_name_question = new FieldPno( 'Födelsedag och sånt', 'Vi rekommenderar att du fyller i fullständigt personnummer.', $read_only );
	    $html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PNO, @$errors['pno'], $person->pno );

	    $person_name_question = new FieldEmail( 'E-postadress' );
	    $html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_EMAIL, @$errors['email'], $person->email );

	    $person_name_question = new FieldPhone( 'Telefonnummer' );
	    $html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_PHONE, @$errors['phone'], $person->phone );

	    $person_name_question = new FieldText( 'Allergier och matönskemål', 'Arrangemanget är köttfritt och nötfritt. Fyll i här om du har ytterligare behov.', $read_only );
	    $html_sections[]      = $this->render_field( $person_name_question, self::FIELD_PERSON_FOOD, @$errors['food'], $person->food );


	    if ( ! $read_only ) {
            $html_sections[] = sprintf('<div class="tuja-buttons"><button type="submit" name="%s" value="%s">%s</button></div>',
                self::ACTION_BUTTON_NAME,
                self::ACTION_NAME_SAVE,
                'Spara');
        } else {
            $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>',
                sprintf('Du kan inte längre ändra din anmälan. Kontakta <a href="mailto:%s">%s</a> om du behöver ändra något.',
                    get_bloginfo('admin_email'),
                    get_bloginfo('admin_email')));
        }

        return sprintf('<form method="post">%s</form>', join($html_sections));
    }

    private function update_person($person)
    {
	    $posted_values = [
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