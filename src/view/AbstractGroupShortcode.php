<?php

namespace tuja\view;

use DateTime;
use Exception;
use tuja\data\model\Competition;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\Person;
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
        $categories = array_filter($this->category_dao->get_all_in_competition($competition_id), function (GroupCategory $category) use ($filter_crew_categories) {
            return $category->get_rule_set()->is_crew() == $filter_crew_categories;
        });
        return $categories;
    }

	protected function get_posted_category( $competition_id ) {
		$selected_category = $_POST[ self::FIELD_GROUP_AGE ];
		$categories        = $this->get_categories( $competition_id );
		$found_category    = array_filter( $categories, function ( GroupCategory $category ) use ( $selected_category ) {
			return $category->name == $selected_category;
		} );
		if ( count( $found_category ) == 1 ) {
			return reset( $found_category );
		}

		return null;
	}

	protected function is_create_allowed( Competition $competition, GroupCategory $category ): bool
    {
        $now = new DateTime();
	    if ( $competition->create_group_start != null && $competition->create_group_start > $now ) {
            return false;
        }
	    if ( $competition->create_group_end != null && $competition->create_group_end < $now ) {
            return false;
        }

	    return ! isset( $category ) || $category->get_rule_set()->is_create_registration_allowed( $competition );
    }

    protected function is_edit_allowed(Group $group): bool
    {
	    if ( $group->is_always_editable ) {
		    return true;
	    }

	    $competition = $this->competition_dao->get( $group->competition_id );
        $now = new DateTime();
        if ($competition->edit_group_start != null && $competition->edit_group_start > $now) {
            return false;
        }
        if ($competition->edit_group_end != null && $competition->edit_group_end < $now) {
            return false;
        }
	    $category = $group->get_derived_group_category();
	    return ! isset( $category ) || $category->get_rule_set()->is_update_registration_allowed( $competition );
    }
}