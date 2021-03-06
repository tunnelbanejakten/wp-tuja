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
				throw new Exception( 'Oj, vi hittade inte laget' ); // Cannot be localized since we don't know which competition we're supposed to check for string overrides
			}
			$this->group = $group;

			Strings::init($group->competition_id);
		}

		if ( $this->group->get_status() == Group::STATUS_DELETED ) {
			throw new Exception( Strings::get( 'group.is_deleted' ) );
		}

		return $this->group;
	}

	function get_content() {
		try {
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
		$person->name  = @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_NAME, $person ) ] ?: @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_EMAIL, $person ) ];
		$person->email = @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_EMAIL, $person ) ];
		$person->phone = @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_PHONE, $person ) ];
		$person->pno   = @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_PNO, $person ) ];
		$person->food  = @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_FOOD, $person ) ];
		$person->note  = @$_POST[ PersonForm::get_field_name( PersonForm::FIELD_NOTE, $person ) ];
		$person->set_status( Person::STATUS_CREATED );
		$role_posted = @$_POST[ self::FIELD_PERSON_ROLE . ( isset( $id ) ? '__' . $id : '' ) ];
		$person->set_type( $role_posted ?: Person::PERSON_TYPE_REGULAR );

		return $person;
	}

	protected function check_group_status( Group $group ) {
		if ( $group->get_status() === Group::STATUS_AWAITING_APPROVAL ) {
			throw new WarningException( Strings::get( 'group.is_on_waiting_list' ) );
		}
	}

	protected function check_event_is_ongoing() {
		$competition      = $this->competition_dao->get( $this->get_group()->competition_id );
		$now              = new DateTime();
		$is_event_ongoing = ( $competition->event_start == null || $competition->event_start <= $now )
		                    && ( $competition->event_end == null || $now <= $competition->event_end );
		if ( ! $is_event_ongoing ) {
			throw new Exception( Strings::get( 'competition.is_not_open_yet' ) );
		}
	}
}