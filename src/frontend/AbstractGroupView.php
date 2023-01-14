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
	private $person_form;

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

			Strings::init( $group->competition_id );
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

	protected function is_edit_allowed( Group $group, bool $update_requested = true, bool $delete_requested = false ): bool {
		return $group->is_edit_allowed( $update_requested, $delete_requested );
	}

	protected function get_person_form(): PersonForm {
		if ( ! isset( $this->person_form ) ) {
			$this->person_form = new PersonForm(
				true,
				false,
				false,
				$this->get_group()->get_category()->get_rules()
			);
		}

		return $this->person_form;
	}

	protected function init_posted_person( $id = null ): Person {
		$person     = new Person();
		$person->id = $id ?: null;
		$role_posted = @$_POST[ self::FIELD_PERSON_ROLE . ( isset( $id ) ? '__' . $id : '' ) ];
		$person->set_type( $role_posted ?: Person::PERSON_TYPE_REGULAR );
		$person->set_status( Person::STATUS_CREATED );
		$this->get_person_form( )->update_with_posted_values( $person );

		return $person;
	}

	protected function check_group_not_on_waiting_list( Group $group ) {
		if ( $group->get_status() === Group::STATUS_AWAITING_APPROVAL ) {
			throw new WarningException( Strings::get( 'group.is_on_waiting_list' ) );
		}
	}

	protected function check_event_is_ongoing() {
		$competition = $this->competition_dao->get( $this->get_group()->competition_id );
		if ( ! $competition->is_ongoing() ) {
			throw new Exception( Strings::get( 'competition.is_not_open_yet' ) );
		}
	}
}
