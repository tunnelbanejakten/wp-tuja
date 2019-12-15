<?php

namespace tuja\frontend;


use DateTime;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MessageTemplateDao;
use tuja\data\store\PersonDao;
use tuja\util\messaging\MessageSender;

abstract class AbstractGroupView extends FrontendView {
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

	public function __construct( string $url, string $group_key, string $title_pattern ) {
		parent::__construct( $url );
		$this->group_dao            = new GroupDao();
		$this->person_dao           = new PersonDao();
		$this->competition_dao      = new CompetitionDao();
		$this->message_template_dao = new MessageTemplateDao();
		$this->message_sender       = new MessageSender();
		$this->group_key            = $group_key;
		$this->title_pattern        = $title_pattern;
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

	protected function is_edit_allowed( Group $group ): bool {
		if ( $group->is_always_editable ) {
			return true;
		}

		$competition = $this->competition_dao->get( $group->competition_id );
		$now         = new DateTime();
		if ( $competition->edit_group_start != null && $competition->edit_group_start > $now ) {
			return false;
		}
		if ( $competition->edit_group_end != null && $competition->edit_group_end < $now ) {
			return false;
		}
		$category = $group->get_derived_group_category();

		return ! isset( $category ) || $category->get_rule_set()->is_update_registration_allowed( $competition );
	}

	protected function init_posted_person( $id = null ): Person {
		$suffix        = isset( $id ) ? '__' . $id : '';
		$person        = new Person();
		$person->name  = $_POST[ self::FIELD_PERSON_NAME . $suffix ] ?: $_POST[ self::FIELD_PERSON_EMAIL . $suffix ];
		$person->email = $_POST[ self::FIELD_PERSON_EMAIL . $suffix ];
		$person->phone = $_POST[ self::FIELD_PERSON_PHONE . $suffix ];
		$person->pno   = $_POST[ self::FIELD_PERSON_PNO . $suffix ];
		$person->food  = $_POST[ self::FIELD_PERSON_FOOD . $suffix ];
		$person->set_status( Person::STATUS_CREATED );

		switch ( $_POST[ self::FIELD_PERSON_ROLE . $suffix ] ) {
			case self::ROLE_ADULT_SUPERVISOR:
				$person->set_as_adult_supervisor();
				break;
			case self::ROLE_EXTRA_CONTACT:
				$person->set_as_extra_contact();
				break;
			case self::ROLE_GROUP_LEADER:
				$person->set_as_group_leader();
				break;
			default:
				$person->set_as_regular_group_member();
				break;
		}

		return $person;
	}
}