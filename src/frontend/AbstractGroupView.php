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
	const FIELD_PERSON_ROLE = self::FIELD_PREFIX_PERSON . 'role';

	const ROLE_ADULT_SUPERVISOR = "adult_supervisor";
	const ROLE_REGULAR_GROUP_MEMBER = "regular_group_member";
	const ROLE_EXTRA_CONTACT = "extra_contact";
	const ROLE_GROUP_LEADER = "group_leader";

	protected $person_dao;
	protected $group_dao;
	protected $message_template_dao;
	protected $competition_dao;
	private $message_sender;
	private $group_key;
	private $title_pattern;

	public function __construct(string $url, string $group_key, string $title_pattern)
	{
		parent::__construct($url);
		$this->group_dao            = new GroupDao();
		$this->person_dao           = new PersonDao();
		$this->competition_dao      = new CompetitionDao();
		$this->message_template_dao = new MessageTemplateDao();
		$this->message_sender       = new MessageSender();
		$this->group_key = $group_key;
		$this->title_pattern = $title_pattern;
	}

	protected function get_group(): Group {
		$group = $this->group_dao->get_by_key( $this->group_key );
		if ( $group == false ) {
			throw new Exception( 'Oj, vi hittade inte laget' );
		}

		return $group;
	}

	function get_title() {
		try {
			return sprintf( $this->title_pattern, $this->get_group()->name );
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
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