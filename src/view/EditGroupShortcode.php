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

    private $group_key;

    public function __construct($wpdb, $group_key, $is_crew_form)
    {
        parent::__construct($wpdb, $is_crew_form);
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
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Inget lag angivet.');
        }
    }

    private function render_update_form($group, $errors = array(), $read_only = false): string
    {
        wp_enqueue_script('tuja-editgroup-script');

        $people = $this->person_dao->get_all_in_group($group->id);

        $html_sections = [];

        if (isset($errors['__'])) {
            $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors['__']);
        }

        $html_sections[] = sprintf('<h3>Laget</h3>');

        $group_name_question = Question::text('Vad heter ert lag?', null, $group->name);
        $html_sections[] = $this->render_field($group_name_question, self::FIELD_GROUP_NAME, $errors['name'], $read_only);

        $categories = $this->get_categories($group->competition_id);

        $current_group_category_name = reset(array_filter($categories, function ($category) use ($group) {
            return $category->id == $group->category_id;
        }))->name;

        $group_category_options = array_map(function ($category) {
            return $category->name;
        }, $categories);

        $group_category_question = Question::dropdown(
            'Vilken klass tävlar ni i?',
            $group_category_options,
            'Välj den som de flesta av deltagarna tillhör.',
            $current_group_category_name
        );
        $html_sections[] = $this->render_field($group_category_question, self::FIELD_GROUP_AGE, $errors['age'], $read_only);

        $html_sections[] = sprintf('<h3>Deltagarna</h3>');

        if (is_array($people)) {
            $html_sections[] = sprintf('<div class="tuja-people-existing">%s</div>', join(array_map(function ($person) use ($errors, $read_only) {
                return $this->render_person_form($person, $errors, $read_only);
            }, $people)));
        }
        if (!$read_only) {
            $html_sections[] = sprintf('<div class="tuja-item-buttons"><button type="button" name="%s" value="%s" class="tuja-add-person">%s</button></div>', self::ACTION_BUTTON_NAME, 'new_person', 'Lägg till deltagare');
            $html_sections[] = sprintf('<div class="tuja-person-template">%s</div>', $this->render_person_form(new Person(), $errors, $read_only));
        }

        if (!$read_only) {
            $html_sections[] = sprintf('<div><button type="submit" name="%s" value="%s">%s</button></div>',
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

    private function render_person_form($person, $errors = array(), $read_only = false): string
    {
        $html_sections = [];

        $random_id = $person->random_id ?: '';

        $person_name_question = Question::text('Namn', null, $person->name);
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_PERSON_NAME . '__' . $random_id, $errors[$random_id . '__name'], $read_only);

        $person_name_question = Question::text('E-postadress', null, $person->email);
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_PERSON_EMAIL . '__' . $random_id, $errors[$random_id . '__email'], $read_only);

        $person_name_question = Question::text('Telefonnummer', null, $person->phone);
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_PERSON_PHONE . '__' . $random_id, $errors[$random_id . '__phone'], $read_only);

        if (!$read_only) {
            $html_sections[] = sprintf('<div class="tuja-item-buttons"><button type="button" name="%s" value="%s%s" class="tuja-delete-person">%s</button></div>',
                self::ACTION_BUTTON_NAME,
                self::ACTION_NAME_DELETE_PERSON_PREFIX,
                $random_id,
                'Ta bort');
        }

        return sprintf('<div class="tuja-signup-person">%s</div>', join($html_sections));
    }

    private function update_group($group)
    {
        $validation_errors = array();
        $overall_success = true;

        $group_id = $group->id;

        $group->name = $_POST[self::FIELD_GROUP_NAME];
        $selected_category = $_POST[self::FIELD_GROUP_AGE];
        $categories = $this->get_categories($group->competition_id);
        $found_category = array_filter($categories, function ($category) use ($selected_category) {
            return $category->name == $selected_category;
        });
        if (count($found_category) == 1) {
            $group->category_id = reset($found_category)->id;
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
                $new_person = new Person();
                $new_person->group_id = $group_id;
                $new_person->name = $_POST[self::FIELD_PREFIX_PERSON . 'name__' . $id];
                $new_person->email = $_POST[self::FIELD_PREFIX_PERSON . 'email__' . $id];
                $new_person->phone = $_POST[self::FIELD_PREFIX_PERSON . 'phone__' . $id];

                $new_person_id = $this->person_dao->create($new_person);
                $this_success = $new_person_id !== false;
                $overall_success = ($overall_success and $this_success);
            } catch (ValidationException $e) {
                $validation_errors['__' . $e->getField()] = $e->getMessage();
                $overall_success = false;
            } catch (Exception $e) {
                $overall_success = false;
            }
        }

        foreach ($updated_ids as $id) {
            if (isset($people_map[$id])) {
                try {
                    $people_map[$id]->name = $_POST[self::FIELD_PREFIX_PERSON . 'name__' . $id];
                    $people_map[$id]->email = $_POST[self::FIELD_PREFIX_PERSON . 'email__' . $id];
                    $people_map[$id]->phone = $_POST[self::FIELD_PREFIX_PERSON . 'phone__' . $id];

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