<?php

namespace tuja\frontend;


use DateTime;
use Exception;
use Throwable;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MessageTemplateDao;
use tuja\data\store\PersonDao;
use tuja\util\messaging\MessageSender;
use tuja\util\Strings;
use tuja\util\WarningException;

abstract class AbstractGroupView extends FrontendView {
	const ROLE_ADULT_SUPERVISOR = Person::PERSON_TYPE_SUPERVISOR;
	const ROLE_REGULAR_GROUP_MEMBER = Person::PERSON_TYPE_REGULAR;
	const ROLE_EXTRA_CONTACT = Person::PERSON_TYPE_ADMIN;
	const ROLE_GROUP_LEADER = Person::PERSON_TYPE_LEADER;

	protected $person_dao;
	protected $group_dao;
	protected $message_template_dao;
	protected $competition_dao;
	private $message_sender;
	private $group_key;
	private $group;
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
		if ( ! isset( $this->group ) ) {

			$group = $this->group_dao->get_by_key( $this->group_key );
			if ( $group === false ) {
				throw new Exception( 'Oj, vi hittade inte laget' );
			}
			$this->group = $group;
		}

		if ( $this->group->get_status() == Group::STATUS_DELETED ) {
			throw new Exception( 'Laget är avanmält.' );
		}

		return $this->group;
	}

	function get_content() {
		try {
			Strings::init( $this->get_group()->competition_id );

			return parent::get_content();
		} catch ( Exception $e ) {
			return $this->get_exception_message_html( $e );
		}
	}


	function get_title() {
		try {
			return sprintf( $this->title_pattern, $this->get_group()->name );
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	protected function is_edit_allowed( Group $group ): bool {
		return $group->is_edit_allowed();
	}

	protected function init_posted_person( $id = null ): Person {
		$person        = new Person();
		$person->id    = $id ?: null;
		$person->name  = $_POST[ PersonForm::get_field_name( PersonForm::FIELD_NAME, $person ) ] ?: $_POST[ PersonForm::get_field_name( PersonForm::FIELD_EMAIL, $person ) ];
		$person->email = $_POST[ PersonForm::get_field_name( PersonForm::FIELD_EMAIL, $person ) ];
		$person->phone = $_POST[ PersonForm::get_field_name( PersonForm::FIELD_PHONE, $person ) ];
		$person->pno   = $_POST[ PersonForm::get_field_name( PersonForm::FIELD_PNO, $person ) ];
		$person->food  = $_POST[ PersonForm::get_field_name( PersonForm::FIELD_FOOD, $person ) ];
		$person->note  = $_POST[ PersonForm::get_field_name( PersonForm::FIELD_NOTE, $person ) ];
		$person->set_status( Person::STATUS_CREATED );

		$suffix = isset( $id ) ? '__' . $id : '';
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

	protected function check_group_status( Group $group ) {
		if ( $group->get_status() === Group::STATUS_AWAITING_APPROVAL ) {
			throw new WarningException( 'Ert lag står på väntelistan och därför är den här sidan låst just nu.' );
		}
	}

	protected function check_event_is_ongoing() {
		$competition      = $this->competition_dao->get( $this->get_group()->competition_id );
		$now              = new DateTime();
		$is_event_ongoing = ( $competition->event_start == null || $competition->event_start <= $now )
		                    && ( $competition->event_end == null || $now <= $competition->event_end );
		if ( ! $is_event_ongoing ) {
			throw new Exception( 'Tävlingen har inte öppnat än.' );
		}
	}
}