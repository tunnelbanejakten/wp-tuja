<?php

namespace view;

use data\model\ValidationException;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\Question;
use tuja\view\Field;
use util\Recaptcha;


// TODO: Unify error handling so that there is no mix of "arrays of error messages" and "exception throwing". Pick one practice, don't mix.
class CreateGroupShortcode extends AbstractGroupShortcode
{

    private $competition_id;

    public function __construct($wpdb, $competition_id)
    {
        parent::__construct($wpdb);
        $this->competition_id = $competition_id;
    }

    public function render(): String
    {
        if ($_POST[self::ACTION_BUTTON_NAME] == self::ACTION_NAME_SAVE) {
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

    private function render_create_form($errors = array()): string
    {
        $html_sections = [];

        if (isset($errors['__'])) {
            $html_sections[] = sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors['__']);
        }

        $group_name_question = Question::create_text('Vad heter ert lag?');
        $html_field = Field::create($group_name_question)->render(self::FIELD_GROUP_NAME);
        $html_sections[] = sprintf('<div class="tuja-question %s">%s%s</div>',
            isset($errors[self::FIELD_GROUP_NAME]) ? 'tuja-field-error' : '',
            $html_field,
            isset($errors[self::FIELD_GROUP_NAME]) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors[self::FIELD_GROUP_NAME]) : '');

        // TODO: Age group is not saved in database
        $person_name_question = Question::create_dropdown(
            'Vilken klass tävlar ni i?',
            array(
                '13-15' => '13-15 år',
                '15-18' => '15-18 år',
                '18' => '18 år och äldre'
            ),
            'Välj den som de flesta av deltagarna tillhör.');
        $html_sections[] = Field::create($person_name_question)->render(self::FIELD_GROUP_AGE);

        $person_name_question = Question::create_text('Vad heter du?');
        $html_field = Field::create($person_name_question)->render(self::FIELD_PERSON_NAME);
        $html_sections[] = sprintf('<div class="tuja-question %s">%s%s</div>',
            isset($errors[self::FIELD_PERSON_NAME]) ? 'tuja-field-error' : '',
            $html_field,
            isset($errors[self::FIELD_PERSON_NAME]) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $errors[self::FIELD_PERSON_NAME]) : '');

        $person_name_question = Question::create_text(
            'Vilken e-postadress har du?',
            'Vi kommer skicka viktig information inför tävlingen till denna adress. Ni kan ändra till en annan adress senare om det skulle behövas.');
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

        $html_sections[] = sprintf('<div><button type="submit" name="%s" value="%s">%s</button></div>', self::ACTION_BUTTON_NAME, self::ACTION_NAME_SAVE, 'Anmäl lag');

        return sprintf('<form method="post">%s</form>', join($html_sections));
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
            throw new ValidationException(self::FIELD_PREFIX_GROUP . $e->getField(), $e->getMessage());
        }

        $new_person = new Person();
        $new_person->name = $_POST[self::FIELD_PERSON_NAME];
        $new_person->email = $_POST[self::FIELD_PERSON_EMAIL];

        try {
            // Person is validated before Group is created in order to catch simple input problems, like a missing name or email address.
            $new_person->validate();
        } catch (ValidationException $e) {
            throw new ValidationException(self::FIELD_PREFIX_PERSON . $e->getField(), $e->getMessage());
        }

        $new_group_id = false;
        try {
            $new_group_id = $this->group_dao->create($new_group);
        } catch (ValidationException $e) {
            throw new ValidationException(self::FIELD_PREFIX_GROUP . $e->getField(), $e->getMessage());
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
                throw new ValidationException(self::FIELD_PREFIX_PERSON . $e->getField(), $e->getMessage());
            }
        } else {
            throw new Exception('Kunde inte anmäla laget.');
        }
    }
}