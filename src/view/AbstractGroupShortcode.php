<?php

namespace tuja\view;

use DateTime;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MessageTemplateDao;
use tuja\data\store\PersonDao;
use tuja\util\messaging\MessageSender;
use tuja\util\Template;
use tuja\util\messaging\OutgoingEmailMessage;

class AbstractGroupShortcode extends AbstractShortcode
{
    const ACTION_BUTTON_NAME = 'tuja-action';
    const ACTION_NAME_SAVE = 'save';

    const FIELD_PREFIX_PERSON = 'tuja-person__';
    const FIELD_PREFIX_GROUP = 'tuja-group__';
    const FIELD_GROUP_NAME = self::FIELD_PREFIX_GROUP . 'name';
    const FIELD_GROUP_AGE = self::FIELD_PREFIX_GROUP . 'age';
    const FIELD_PERSON_NAME = self::FIELD_PREFIX_PERSON . 'name';
    const FIELD_PERSON_EMAIL = self::FIELD_PREFIX_PERSON . 'email';
    const FIELD_PERSON_PHONE = self::FIELD_PREFIX_PERSON . 'phone';
    const FIELD_PERSON_PNO = self::FIELD_PREFIX_PERSON . 'pno';
    const FIELD_PERSON_FOOD = self::FIELD_PREFIX_PERSON . 'food';
    const FIELD_PERSON_ROLES = self::FIELD_PREFIX_PERSON . 'roles';

    protected $person_dao;
    protected $group_dao;
    protected $category_dao;
    protected $message_template_dao;
    protected $competition_dao;
    private $is_crew_form;
    private $message_sender;

    public function __construct($wpdb, $is_crew_form)
    {
	    parent::__construct();
	    $this->group_dao            = new GroupDao();
	    $this->person_dao           = new PersonDao();
	    $this->competition_dao      = new CompetitionDao();
	    $this->category_dao         = new GroupCategoryDao();
	    $this->message_template_dao = new MessageTemplateDao();
	    $this->is_crew_form         = $is_crew_form;
	    $this->message_sender       = new MessageSender();
    }

    protected function render_field( Field $field, $field_name, $error_message, $answer_object = null ): string
    {
    	// TODO: This is a bit of a hack...
	    if ( is_scalar($answer_object) ) {
		    $answer_object = [ $answer_object ];
	    }
	    $html = $field->render( $field_name, $answer_object );

        return sprintf('<div class="tuja-question %s">%s%s</div>',
            !empty($error_message) ? 'tuja-field-error' : '',
            $html,
            !empty($error_message) ? sprintf('<p class="tuja-message tuja-message-error">%s</p>', $error_message) : '');
    }

    protected function get_categories($competition_id): array
    {
        $filter_crew_categories = $this->is_crew_form == true;
        $categories = array_filter($this->category_dao->get_all_in_competition($competition_id), function ($category) use ($filter_crew_categories) {
            return $category->is_crew == $filter_crew_categories;
        });
        return $categories;
    }

    protected function is_create_allowed($competition_id): bool
    {
        $form = $this->competition_dao->get($competition_id);
        $now = new DateTime();
        if ($form->create_group_start != null && $form->create_group_start > $now) {
            return false;
        }
        if ($form->create_group_end != null && $form->create_group_end < $now) {
            return false;
        }
        return true;
    }

    protected function is_edit_allowed($competition_id): bool
    {
        $form = $this->competition_dao->get($competition_id);
        $now = new DateTime();
        if ($form->edit_group_start != null && $form->edit_group_start > $now) {
            return false;
        }
        if ($form->edit_group_end != null && $form->edit_group_end < $now) {
            return false;
        }
        return true;
    }

    protected function send_template_mail($to, $template_id, Group $group, Person $person)
    {
        $message_template = $this->message_template_dao->get($template_id);

        $template_parameters = array_merge(
            Template::site_parameters(),
            Template::person_parameters($person),
            Template::group_parameters($group)
        );
        $outgoing_message = new OutgoingEmailMessage($this->message_sender,
            Person::from_email($to),
            Template::string($message_template->body)->render($template_parameters, true),
            Template::string($message_template->subject)->render($template_parameters));

        try {
            $outgoing_message->send();
        } catch (Exception $e) {
        }
    }
}