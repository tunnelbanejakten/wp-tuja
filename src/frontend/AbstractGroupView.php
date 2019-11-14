<?php

namespace tuja\frontend;


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
use tuja\util\messaging\OutgoingEmailMessage;
use tuja\util\Template;
use tuja\view\Field;

abstract class AbstractGroupView extends FrontendView {

	const FIELD_PERSON_NAME = self::FIELD_PREFIX_PERSON . 'name';
	const FIELD_PERSON_EMAIL = self::FIELD_PREFIX_PERSON . 'email';
	const FIELD_PERSON_PHONE = self::FIELD_PREFIX_PERSON . 'phone';
	const FIELD_PERSON_PNO = self::FIELD_PREFIX_PERSON . 'pno';
	const FIELD_PERSON_FOOD = self::FIELD_PREFIX_PERSON . 'food';
	const FIELD_PERSON_ROLES = self::FIELD_PREFIX_PERSON . 'roles';
	const FIELD_PERSON_ISCOMPETING = self::FIELD_PREFIX_PERSON . 'iscompeting';
	const FIELD_PERSON_ISCONTACT = self::FIELD_PREFIX_PERSON . 'iscontact';

	protected $person_dao;
	protected $group_dao;
	protected $message_template_dao;
	protected $competition_dao;
	private $is_crew_form;
	private $message_sender;

	public function __construct($url, $is_crew_form)
	{
		parent::__construct($url);
		$this->group_dao            = new GroupDao();
		$this->person_dao           = new PersonDao();
		$this->competition_dao      = new CompetitionDao();
		$this->message_template_dao = new MessageTemplateDao();
		$this->is_crew_form         = $is_crew_form;
		$this->message_sender       = new MessageSender();
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