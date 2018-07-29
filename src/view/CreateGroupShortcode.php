<?php

namespace view;

use data\model\ValidationException;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\Question;
use util\messaging\MessageSender;
use util\Recaptcha;
use util\Template;


// TODO: Unify error handling so that there is no mix of "arrays of error messages" and "exception throwing". Pick one practice, don't mix.
class CreateGroupShortcode extends AbstractGroupShortcode
{

    private $competition_id;
    private $edit_link_template;

    private $message_sender;

    public function __construct($wpdb, $competition_id, $edit_link_template)
    {
        parent::__construct($wpdb);
        $this->competition_id = $competition_id;
        $this->edit_link_template = $edit_link_template;
        $this->message_sender = new MessageSender();
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

                $edit_link = sprintf($this->edit_link_template, $new_group->random_id);
                if (!empty($edit_link)) {
                    return sprintf('<p class="tuja-message tuja-message-success">Tack! Nästa steg är att gå till <a href="%s">%s</a> och fylla i vad de andra deltagarna i ert lag heter. Vi har också skickat länken till din e-postadress så att du kan ändra er anmälan framöver.</p>', $edit_link, $edit_link);
                } else {
                    return sprintf('<p class="tuja-message tuja-message-success">Tack för din anmälan.</p>');
                }
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

        $group_name_question = Question::text('Vad heter ert lag?');
        $html_sections[] = $this->render_field($group_name_question, self::FIELD_GROUP_NAME, $errors[self::FIELD_GROUP_NAME]);

        // TODO: Age group is not saved in database
        $person_name_question = Question::dropdown(
            'Vilken klass tävlar ni i?',
            array(
                '13-15' => '13-15 år',
                '15-18' => '15-18 år',
                '18' => '18 år och äldre'
            ),
            'Välj den som de flesta av deltagarna tillhör.');
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_GROUP_AGE, $errors[self::FIELD_GROUP_AGE]);

        $person_name_question = Question::text('Vad heter du?');
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_PERSON_NAME, $errors[self::FIELD_PERSON_NAME]);

        $person_name_question = Question::text(
            'Vilken e-postadress har du?',
            'Vi kommer skicka viktig information inför tävlingen till denna adress. Ni kan ändra till en annan adress senare om det skulle behövas.');
        $html_sections[] = $this->render_field($person_name_question, self::FIELD_PERSON_EMAIL, $errors[self::FIELD_PERSON_EMAIL]);

        $recaptcha_sitekey = get_option('tuja_recaptcha_sitekey');
        if (!empty($recaptcha_sitekey)) {
            wp_enqueue_script('tuja-recaptcha-script');
            $html_sections[] = sprintf('<div class="tuja-robot-check"><div class="g-recaptcha" data-sitekey="%s"></div></div>', $recaptcha_sitekey);
        }

        $html_sections[] = sprintf('<div><button type="submit" name="%s" value="%s">%s</button></div>', self::ACTION_BUTTON_NAME, self::ACTION_NAME_SAVE, 'Anmäl lag');

        return sprintf('<form method="post">%s</form>', join($html_sections));
    }

    // TODO: create_group does a bit too much application logic to be in a presentation class. Extract application logic to some utility class.
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
                $new_person_id = $this->person_dao->create($new_person);
                if ($new_person_id !== false) {

                    $group = $this->group_dao->get($new_group_id);

                    $this->send_group_welcome_mail($new_person->email, $group);

                    $admin_email = get_option('admin_email');
                    if (!empty($admin_email)) {
                        $this->send_group_admin_mail($admin_email, $group);
                    }

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

    private function send_group_welcome_mail($to, $group): void
    {
        $mail_result = $this->message_sender->send_mail($to,
            'Er anmälan är nästan klar',
            Template::file('util/messaging/signup_group_participant.html')->render([
                'link' => sprintf($this->edit_link_template, $group->random_id)
            ]));
        if (!$mail_result) {
//            throw new Exception('Ett fel uppstod. Vi vet tyvärr inte riktigt varför.');
        }
    }

    private function send_group_admin_mail($to, $group): void
    {
        $mail_result = $this->message_sender->send_mail($to,
            'Anmälan från ' . $group->name,
            Template::file('util/messaging/signup_group_admin.html')->render([
                'group_name' => htmlspecialchars($group->name),
                'link' => sprintf($this->edit_link_template, $group->random_id)
            ]));
        if (!$mail_result) {
//            throw new Exception('Ett fel uppstod. Vi vet tyvärr inte riktigt varför.');
        }
    }
}