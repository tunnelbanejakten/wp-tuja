<?php

namespace view;

use data\model\ValidationException;
use Exception;
use tuja\data\model\Person;
use tuja\data\model\Question;
use tuja\data\model\Response;


// TODO: Unify error handling so that there is no mix of "arrays of error messages" and "exception throwing". Pick one practice, don't mix.
class EditGroupShortcode extends AbstractGroupShortcode
{
    const ACTION_NAME_DELETE_PERSON_PREFIX = 'delete_person__';

    private $group_key;

    public function __construct($wpdb, $group_key)
    {
        parent::__construct($wpdb);
        $this->group_key = $group_key;
    }

    public function render(): String
    {
        $group_key = $this->group_key;

        if (isset($group_key)) {
            $group = $this->group_dao->get_by_key($group_key);
            if ($group === false) {
                return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Inget lag angivet.');
            }

            if (substr($_POST[self::ACTION_BUTTON_NAME], 0, strlen(self::ACTION_NAME_DELETE_PERSON_PREFIX)) == self::ACTION_NAME_DELETE_PERSON_PREFIX) {
                try {
                    $person_to_delete = substr($_POST[self::ACTION_BUTTON_NAME], strlen(self::ACTION_NAME_DELETE_PERSON_PREFIX));
                    $this->delete_person($person_to_delete);
                } catch (Exception $e) {
                    return $this->render_update_form($group, array('__' => $e->getMessage()));
                }
            } elseif ($_POST[self::ACTION_BUTTON_NAME] == self::ACTION_NAME_SAVE) {
                $errors = $this->update_group($group);
                return $this->render_update_form($group, $errors);
            }
            return $this->render_update_form($group);
        } else {
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Inget lag angivet.');
        }
    }

    private function render_update_form($group, $errors = array()): string
    {
        $people = $this->person_dao->get_all_in_group($group->id);

        $html_sections = [];

        if (isset($errors['__'])) {
            $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors['__']);
        }

        $html_sections[] = sprintf('<h3>Laget</h3>');

        $group_name_question = Question::text('Vad heter ert lag?', null, new Response($group->name));
        $html_sections[] = $this->render_field($group_name_question, self::FIELD_GROUP_NAME, $errors['name']);

        $person_name_question = Question::dropdown(
            'Vilken klass tävlar ni i?',
            array(
                '13-15' => '13-15 år',
                '15-18' => '15-18 år',
                '18' => '18 år och äldre'
            ),
            'Välj den som de flesta av deltagarna tillhör.');
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_GROUP_AGE, $errors['age']);

        if (is_array($people)) {
            foreach ($people as $index => $person) {
                $html_sections[] = $this->render_person_form($person, $index + 1, $errors);
            }
        }
        $html_sections[] = $this->render_person_form(new Person(), -1, $errors);

        $html_sections[] = sprintf('<div><button type="submit" name="%s" value="%s">%s</button></div>', self::ACTION_BUTTON_NAME, self::ACTION_NAME_SAVE, 'Uppdatera anmälan');

        return sprintf('<form method="post">%s</form>', join($html_sections));
    }

    private function render_person_form($person, $number, $errors = array()): string
    {
        $html_sections = [];
        if (isset($person->id)) {
            $html_sections[] = sprintf('<h3>Deltagare %s</h3>', $number);
        } else {
            $html_sections[] = sprintf('<h3>Ytterligare en deltagare</h3>');
            $html_sections[] = sprintf('<p><span class="tuja-question-hint">Lägg till en deltagare genom att fylla i uppgifterna och tryck på Uppdatera anmälan-knappen. Därefter kan du lägga till ytterligare en deltagare om du behöver.</span></p>');
        }

        $random_id = $person->random_id ?: '';

        $person_name_question = Question::text('Namn', null, new Response($person->name));
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_PERSON_NAME . '__' . $random_id, $errors[$random_id . '__name']);

        $person_name_question = Question::text('E-postadress', null, new Response($person->email));
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_PERSON_EMAIL . '__' . $random_id, $errors[$random_id . '__email']);

        $person_name_question = Question::text('Telefonnummer', null, new Response($person->phone));
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_PERSON_PHONE . '__' . $random_id, $errors[$random_id . '__phone']);

        if (isset($person->id)) {
            $html_sections[] = sprintf('<div class="tuja-item-buttons"><button type="submit" name="%s" value="%s%s">%s</button></div>', self::ACTION_BUTTON_NAME, self::ACTION_NAME_DELETE_PERSON_PREFIX, $random_id, 'Ta bort');
        }

        return sprintf('<div class="tuja-signup-person">%s</div>', join($html_sections));
    }

    private function update_group($group)
    {
        $validation_errors = array();
        $overall_success = true;

        $group_id = $group->id;

        $group->name = $_POST[self::FIELD_GROUP_NAME];

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

        $form_values = array_filter($_POST, function ($key) {
            return substr($key, 0, strlen(self::FIELD_PREFIX_PERSON)) === self::FIELD_PREFIX_PERSON;
        }, ARRAY_FILTER_USE_KEY);

        $people = $this->person_dao->get_all_in_group($group_id);

        $updated_people = array_combine(array_map(function ($person) {
            return $person->random_id;
        }, $people), $people);
        $new_person = null;
        foreach ($form_values as $field_name => $field_value) {
            list(, $attr, $id) = explode('__', $field_name);
            if (empty($id)) {
                if (!isset($new_person)) {
                    $new_person = new Person();
                    $new_person->group_id = $group_id;
                }
                $current_person = $new_person;
            } else {
                $current_person = $updated_people[$id];
            }
            // TODO: Iterate over array of field names instead of a switch case for each?
            switch ($attr) {
                case 'name':
                    $current_person->name = $field_value;
                    break;
                case 'email':
                    $current_person->email = $field_value;
                    break;
                case 'phone':
                    $current_person->phone = $field_value;
                    break;
            }
        }

        foreach ($updated_people as $updated_person) {
            try {
                $affected_rows = $this->person_dao->update($updated_person);
                $this_success = $affected_rows !== false;
                $overall_success = ($overall_success and $this_success);
            } catch (ValidationException $e) {
                $validation_errors[$updated_person->random_id . '__' . $e->getField()] = $e->getMessage();
                $overall_success = false;
            } catch (Exception $e) {
                $overall_success = false;
            }
        }
        if (isset($new_person) && !empty($new_person->name)) {
            try {
                $new_person_id = $this->person_dao->create($new_person);
                $this_success = $new_person_id !== false;
                if ($this_success) {
                    // Clear the "new person form" after successfully adding a new person to the group.
                    unset($_POST[self::FIELD_PREFIX_PERSON . 'name__']);
                    unset($_POST[self::FIELD_PREFIX_PERSON . 'email__']);
                    unset($_POST[self::FIELD_PREFIX_PERSON . 'phone__']);
                }
                $overall_success = ($overall_success and $this_success);
            } catch (ValidationException $e) {
                $validation_errors['__' . $e->getField()] = $e->getMessage();
                $overall_success = false;
            } catch (Exception $e) {
                $overall_success = false;
            }
        }
        if (!$overall_success) {
            $validation_errors['__'] = 'Alla ändringar kunde inte sparas.';
        }

        return $validation_errors;
    }

    private function delete_person($person_to_delete)
    {
        $success = $this->person_dao->delete_by_key($person_to_delete);
        if (!$success) {
            throw new Exception('Det gick inte att ta bort deltagaren.');
        }
    }
}