<?php

namespace view;

use data\model\ValidationException;
use data\store\GroupDao;
use data\store\PersonDao;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\Question;
use tuja\data\model\Response;
use tuja\view\Field;
use util\Recaptcha;

const SIGNUP_PARTICIPANTS_FIELD_PREFIX_PERSON = 'tuja-person__';
const SIGNUP_PARTICIPANTS_FIELD_PREFIX_GROUP = 'tuja-group__';
const ACTION_NAME_DELETE_PERSON_PREFIX = 'delete_person__';
const ACTION_NAME_SAVE = 'save';

// TODO: Split into two classes, one for creating a group (registering a new group) and one for editing a group (changing a registration)
// TODO: Unify error handling so that there is no mix of "arrays of error messages" and "exception throwing". Pick one practice, don't mix.
class SignupParticipantsShortcode
{
    const FIELD_GROUP_NAME = SIGNUP_PARTICIPANTS_FIELD_PREFIX_GROUP . 'name';
    const FIELD_GROUP_AGE = SIGNUP_PARTICIPANTS_FIELD_PREFIX_GROUP . 'age';
    const FIELD_PERSON_NAME = SIGNUP_PARTICIPANTS_FIELD_PREFIX_PERSON . 'name';
    const FIELD_PERSON_EMAIL = SIGNUP_PARTICIPANTS_FIELD_PREFIX_PERSON . 'email';
    const FIELD_PERSON_PHONE = SIGNUP_PARTICIPANTS_FIELD_PREFIX_PERSON . 'phone';

    private $group_dao;
    private $person_dao;
    private $competition_id;
    private $group_key;

    public function __construct($wpdb, $competition_id, $group_key)
    {
        $this->competition_id = $competition_id;
        $this->group_key = $group_key;
        $this->group_dao = new GroupDao($wpdb);
        $this->person_dao = new PersonDao($wpdb);
    }

    public function render(): String
    {
        $group_key = $this->group_key;

        if (isset($group_key)) {
            $group = $this->group_dao->get_by_key($group_key);
            if ($group === false) {
                return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Inget lag angivet.');
            }

            if (substr($_POST['tuja_signupparticipantsshortcode_action'], 0, strlen(ACTION_NAME_DELETE_PERSON_PREFIX)) == ACTION_NAME_DELETE_PERSON_PREFIX) {
                try {
                    $person_to_delete = substr($_POST['tuja_signupparticipantsshortcode_action'], strlen(ACTION_NAME_DELETE_PERSON_PREFIX));
                    $this->delete_person($person_to_delete);
                } catch (Exception $e) {
                    return $this->render_update_form($group, array('__' => $e->getMessage()));
                }
            } elseif ($_POST['tuja_signupparticipantsshortcode_action'] == ACTION_NAME_SAVE) {
                $errors = $this->update_group($group);
                return $this->render_update_form($group, $errors);
            }
            return $this->render_update_form($group);
        } else {
            if ($_POST['tuja_signupparticipantsshortcode_action'] == ACTION_NAME_SAVE) {
                try {
                    $recaptcha_secret = get_option('tuja_recaptcha_sitesecret');
                    if (!empty($recaptcha_secret)) {
                        $recaptcha = new Recaptcha($recaptcha_secret);
                        $recaptcha->verify($_POST['g-recaptcha-response']);
                    }

                    // TODO: It's a bit odd that create_group and delete_person throw exceptions whereas update_group returns an arror of error messages.
                    $new_group = $this->create_group();

                    // TODO: Handle https links as well.
                    $current_url = "http://{$_SERVER['SERVER_NAME']}:{$_SERVER['SERVER_PORT']}{$_SERVER['REQUEST_URI']}";
                    $edit_link = rtrim($current_url, '/') . "/team-{$new_group->random_id}";
                    return sprintf('<p class="tuja-message tuja-message-success">Tack! Nästa steg är att gå till <a href="%s">%s</a> och fylla i vad de andra deltagarna i ert lag heter. Vi har också skickat länken till din e-postadress så att du kan ändra er anmälan framöver.</p>', $edit_link, $edit_link);
                } catch (ValidationException $e) {
                    return $this->render_create_form(array($e->getField() => $e->getMessage()));
                } catch (Exception $e) {
                    // TODO: Create helper method for generating field names based on "group or person" and attribute name.
                    return $this->render_create_form(array('__' => $e->getMessage()));
                }
            } else {
                return $this->render_create_form();
            }
        }
    }

    private function render_create_form($errors = array()): string
    {
        $html_sections = [];

        if (isset($errors['__'])) {
            $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors['__']);
        }

        $group_name_question = new Question();
        $group_name_question->type = 'text';
        $group_name_question->text = 'Vad heter ert lag?';
        $group_name_question->latest_response = new Response();
        $html_field = Field::create($group_name_question)->render(self::FIELD_GROUP_NAME);
        $html_sections[] = sprintf('<div class="tuja-question %s">%s%s</div>',
            isset($errors[self::FIELD_GROUP_NAME]) ? 'tuja-field-error' : '',
            $html_field,
            isset($errors[self::FIELD_GROUP_NAME]) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors[self::FIELD_GROUP_NAME]) : '');

        // TODO: Age group is not saved in database
        $person_name_question = new Question();
        $person_name_question->type = 'dropdown';
        $person_name_question->text = 'Vilken klass tävlar ni i?';
        $person_name_question->text_hint = 'Välj den som de flesta av deltagarna tillhör.';
        $person_name_question->set_answer_one_of(array(
            '13-15' => '13-15 år',
            '15-18' => '15-18 år',
            '18' => '18 år och äldre'
        ));
        $html_sections[] = Field::create($person_name_question)->render(self::FIELD_GROUP_AGE);

        $person_name_question = new Question();
        $person_name_question->type = 'text';
        $person_name_question->text = 'Vad heter du?';
        $person_name_question->latest_response = new Response();
        $html_field = Field::create($person_name_question)->render(self::FIELD_PERSON_NAME);
        $html_sections[] = sprintf('<div class="tuja-question %s">%s%s</div>',
            isset($errors[self::FIELD_PERSON_NAME]) ? 'tuja-field-error' : '',
            $html_field,
            isset($errors[self::FIELD_PERSON_NAME]) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors[self::FIELD_PERSON_NAME]) : '');

        $person_name_question = new Question();
        $person_name_question->type = 'text';
        $person_name_question->text = 'Vilken e-postadress har du?';
        $person_name_question->text_hint = 'Vi kommer skicka viktig information inför tävlingen till denna adress. Ni kan ändra till en annan adress senare om det skulle behövas.';
        $person_name_question->latest_response = new Response();
        $html_field = Field::create($person_name_question)->render(self::FIELD_PERSON_EMAIL);
        $html_sections[] = sprintf('<div class="tuja-question %s">%s%s</div>',
            isset($errors[self::FIELD_PERSON_EMAIL]) ? 'tuja-field-error' : '',
            $html_field,
            isset($errors[self::FIELD_PERSON_EMAIL]) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors[self::FIELD_PERSON_EMAIL]) : '');

        $recaptcha_sitekey = get_option('tuja_recaptcha_sitekey');
        if (!empty($recaptcha_sitekey)) {
            wp_enqueue_script('tuja-recaptcha-script');
            $html_sections[] = sprintf('<div class="tuja-robot-check"><div class="g-recaptcha" data-sitekey="%s"></div></div>', $recaptcha_sitekey);
        }

        $html_sections[] = sprintf('<div><button type="submit" name="tuja_signupparticipantsshortcode_action" value="%s">%s</button></div>', ACTION_NAME_SAVE, 'Anmäl lag');

        return sprintf('<form method="post">%s</form>', join($html_sections));
    }

    private function render_update_form($group, $errors = array()): string
    {
        $people = $this->person_dao->get_all_in_group($group->id);

        $html_sections = [];

        if (isset($errors['__'])) {
            $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors['__']);
        }

        $html_sections[] = sprintf('<h3>Laget</h3>');

        // TODO: Implement constructors or factory methods for common Question objects.
        $group_name_question = new Question();
        $group_name_question->type = 'text';
        $group_name_question->text = 'Vad heter ert lag?';
        $group_name_question->latest_response = new Response();
        $group_name_question->latest_response->answer = $group->name;
        $html_field = Field::create($group_name_question)->render(self::FIELD_GROUP_NAME);
        $html_sections[] = sprintf('<div class="tuja-question %s">%s%s</div>',
            isset($errors['name']) ? 'tuja-field-error' : '',
            $html_field,
            isset($errors['name']) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors['name']) : '');

        $person_name_question = new Question();
        $person_name_question->type = 'dropdown';
        $person_name_question->text = 'Vilken klass tävlar ni i?';
        $person_name_question->text_hint = 'Välj den som de flesta av deltagarna tillhör.';
        $person_name_question->set_answer_one_of(array(
            '13-15' => '13-15 år',
            '15-18' => '15-18 år',
            '18' => '18 år och äldre'
        ));
        $html_field = Field::create($person_name_question)->render(self::FIELD_GROUP_AGE);
        $html_sections[] = sprintf('<div class="tuja-question %s">%s%s</div>',
            isset($errors['age']) ? 'tuja-field-error' : '',
            $html_field,
            isset($errors['age']) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors['age']) : '');

        if (is_array($people)) {
            foreach ($people as $index => $person) {
                $html_sections[] = $this->render_person_form($person, $index + 1, $errors);
            }
        }
        $html_sections[] = $this->render_person_form(new Person(), -1, $errors);

        $html_sections[] = sprintf('<div><button type="submit" name="tuja_signupparticipantsshortcode_action" value="%s">%s</button></div>', ACTION_NAME_SAVE, 'Uppdatera anmälan');

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

        $person_name_question = new Question();
        $person_name_question->type = 'text';
        $person_name_question->text = 'Namn';
        $person_name_question->latest_response = new Response();
        $person_name_question->latest_response->answer = $person->name;
        $html_field = Field::create($person_name_question)->render(self::FIELD_PERSON_NAME . '__' . $random_id);
        $html_sections[] = sprintf('<div class="tuja-question %s">%s%s</div>',
            isset($errors[$random_id . '__name']) ? 'tuja-field-error' : '',
            $html_field,
            isset($errors[$random_id . '__name']) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors[$random_id . '__name']) : '');

        $person_name_question = new Question();
        $person_name_question->type = 'text';
        $person_name_question->text = 'E-postadress';
        $person_name_question->latest_response = new Response();
        $person_name_question->latest_response->answer = $person->email;
        $html_field = Field::create($person_name_question)->render(self::FIELD_PERSON_EMAIL . '__' . $random_id);
        $html_sections[] = sprintf('<div class="tuja-question %s">%s%s</div>',
            isset($errors[$random_id . '__email']) ? 'tuja-field-error' : '',
            $html_field,
            isset($errors[$random_id . '__email']) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors[$random_id . '__email']) : '');

        $person_name_question = new Question();
        $person_name_question->type = 'text';
        $person_name_question->text = 'Telefonnummer';
        $person_name_question->latest_response = new Response();
        $person_name_question->latest_response->answer = $person->phone;
        $html_field = Field::create($person_name_question)->render(self::FIELD_PERSON_PHONE . '__' . $random_id);
        $html_sections[] = sprintf('<div class="tuja-question %s">%s%s</div>',
            isset($errors[$random_id . '__phone']) ? 'tuja-field-error' : '',
            $html_field,
            isset($errors[$random_id . '__phone']) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors[$random_id . '__phone']) : '');

        if (isset($person->id)) {
            $html_sections[] = sprintf('<div class="tuja-item-buttons"><button type="submit" name="tuja_signupparticipantsshortcode_action" value="%s%s">%s</button></div>', ACTION_NAME_DELETE_PERSON_PREFIX, $random_id, 'Ta bort');
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
            $this->group_dao->update($group);
        } catch (ValidationException $e) {
            $validation_errors[$e->getField()] = $e->getMessage();
            $overall_success = false;
        } catch (Exception $e) {
            $overall_success = false;
        }

        $form_values = array_filter($_POST, function ($key) {
            return substr($key, 0, strlen(SIGNUP_PARTICIPANTS_FIELD_PREFIX_PERSON)) === SIGNUP_PARTICIPANTS_FIELD_PREFIX_PERSON;
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
                // TODO: Number of affected rows is 0 if an existing person is untouched. This will be interpreted as an error since we always expect 1 affected row.
                $this_success = $affected_rows !== false && $affected_rows === 1;
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
                $affected_rows = $this->person_dao->create($new_person);
                $this_success = $affected_rows !== false && $affected_rows === 1;
                if ($this_success) {
                    // Clear the "new person form" after successfully adding a new person to the group.
                    unset($_POST[SIGNUP_PARTICIPANTS_FIELD_PREFIX_PERSON . 'name__']);
                    unset($_POST[SIGNUP_PARTICIPANTS_FIELD_PREFIX_PERSON . 'email__']);
                    unset($_POST[SIGNUP_PARTICIPANTS_FIELD_PREFIX_PERSON . 'phone__']);
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

    private function create_group(): Group
    {
        $new_group = new Group();
        $new_group->name = $_POST[self::FIELD_GROUP_NAME];
        $new_group->type = 'participant';
        $new_group->competition_id = $this->competition_id;

        try {
            $new_group->validate();
        } catch (ValidationException $e) {
            throw new ValidationException(SIGNUP_PARTICIPANTS_FIELD_PREFIX_GROUP . $e->getField(), $e->getMessage());
        }

        $new_person = new Person();
        $new_person->name = $_POST[self::FIELD_PERSON_NAME];
        $new_person->email = $_POST[self::FIELD_PERSON_EMAIL];

        try {
            // Person is validated before Group is created in order to catch simple input problems, like a missing name or email address.
            $new_person->validate();
        } catch (ValidationException $e) {
            throw new ValidationException(SIGNUP_PARTICIPANTS_FIELD_PREFIX_PERSON . $e->getField(), $e->getMessage());
        }

        $new_group_id = false;
        try {
            $new_group_id = $this->group_dao->create($new_group);
        } catch (ValidationException $e) {
            throw new ValidationException(SIGNUP_PARTICIPANTS_FIELD_PREFIX_GROUP . $e->getField(), $e->getMessage());
        }
        if ($new_group_id !== false) {
            $new_person->group_id = $new_group_id;
            try {
                $affected_rows = $this->person_dao->create($new_person);
                if ($affected_rows !== false && $affected_rows == 1) {

                    $group = $this->group_dao->get($new_group_id);

                    return $group;
                } else {
                    throw new Exception('Ett fel uppstod. Vi vet tyvärr inte riktigt varför.');
                }
            } catch (ValidationException $e) {
                throw new ValidationException(SIGNUP_PARTICIPANTS_FIELD_PREFIX_PERSON . $e->getField(), $e->getMessage());
            }
        } else {
            // TODO: Check for existing groups instead of relying on database constraint.
            throw new Exception('Kunde inte anmäla laget. Kanske finns redan ett lag med samma namn?');
        }
    }

    private function delete_person($person_to_delete)
    {
        $success = $this->person_dao->delete_by_key($person_to_delete);
        if (!$success) {
            throw new Exception('Det gick inte att ta bort deltagaren.');
        }
    }
}