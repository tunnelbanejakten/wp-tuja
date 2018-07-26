<?php

namespace view;

use data\model\ValidationException;
use Exception;
use tuja\data\model\Question;
use tuja\data\model\Response;


// TODO: Unify error handling so that there is no mix of "arrays of error messages" and "exception throwing". Pick one practice, don't mix.
class EditPersonShortcode extends AbstractGroupShortcode
{
    private $person_key;

    public function __construct($wpdb, $person_key)
    {
        parent::__construct($wpdb);
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

            if ($_POST[self::ACTION_BUTTON_NAME] == self::ACTION_NAME_SAVE) {
                // TODO: Give feedback to user if update was successful.
                $errors = $this->update_person($person);
                return $this->render_update_form($person, $errors);
            }
            return $this->render_update_form($person);
        } else {
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Ingen person angiven.');
        }
    }

    private function render_update_form($person, $errors = array()): string
    {
        $html_sections = [];

        if (isset($errors['__'])) {
            $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors['__']);
        }

        $person_name_question = Question::text('Namn', null, new Response($person->name));
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_PERSON_NAME, $errors['name']);

        $person_name_question = Question::text('E-postadress', null, new Response($person->email));
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_PERSON_EMAIL, $errors['email']);

        $person_name_question = Question::text('Telefonnummer', null, new Response($person->phone));
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_PERSON_PHONE, $errors['phone']);


        $html_sections[] = sprintf('<div><button type="submit" name="%s" value="%s">%s</button></div>', self::ACTION_BUTTON_NAME, self::ACTION_NAME_SAVE, 'Uppdatera anmälan');

        return sprintf('<form method="post">%s</form>', join($html_sections));
    }

    private function update_person($person)
    {
        $validation_errors = array();
        $overall_success = true;

        $person->name = $_POST[self::FIELD_PERSON_NAME];
        $person->email = $_POST[self::FIELD_PERSON_EMAIL];
        $person->phone = $_POST[self::FIELD_PERSON_PHONE];

        try {
            $affected_rows = $this->person_dao->update($person);
            if ($affected_rows === false) {
                $overall_success = false;
            }
        } catch (ValidationException $e) {
            $validation_errors[$e->getField()] = $e->getMessage();
            $overall_success = false;
        } catch (Exception $e) {
            $overall_success = false;
        }

        if (!$overall_success) {
            $validation_errors['__'] = 'Alla ändringar kunde inte sparas.';
        }

        return $validation_errors;
    }

}