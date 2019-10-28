<?php

namespace tuja\util\messaging;


use tuja\data\model\Group;
use tuja\data\model\MessageTemplate;
use tuja\data\model\Person;
use tuja\data\store\GroupDao;
use tuja\data\store\MessageTemplateDao;
use tuja\data\store\PersonDao;
use tuja\util\Template;

class EventMessageSender {

	private $message_sender;
	private $person_dao;
	private $message_template_dao;

	const RECIPIENT_ADMIN = 'admin';
	const RECIPIENT_GROUP_CONTACT = 'groupcontact';
	const RECIPIENT_SELF = 'self';

	private $group_dao;

	public static function group_status_change_event_name( $old_status, $new_status ) {
		return join( '.', [ 'group', 'status', $old_status, $new_status ] );
	}

	public static function new_group_member_event_name( $is_crew_group ) {
		return join( '.', [ 'group', $is_crew_group ? 'crew' : 'non-crew', 'new_person' ] );
	}

	public function __construct() {
		$this->message_template_dao = new MessageTemplateDao();
		$this->person_dao           = new PersonDao();
		$this->message_sender       = new MessageSender();
		$this->group_dao            = new GroupDao();
	}

	public function send_new_person_messages( Person $person ) {

		$group = $this->group_dao->get($person->group_id);

		$group_category = $group->get_derived_group_category();

		$is_crew_group = isset( $group_category ) && $group_category->is_crew;

		$event_name        = self::new_group_member_event_name( $is_crew_group );
		$message_templates = $this->message_template_dao->get_for_event( $group->competition_id, $event_name );

		array_walk( $message_templates, function ( MessageTemplate $mt ) use ( $person, $group ) {
			$contacts = [];
			switch ( $mt->auto_send_recipient ) {
				case self::RECIPIENT_ADMIN:
					$contacts = [ Person::from_email( get_option( 'admin_email' ) ) ];
					break;
				case self::RECIPIENT_SELF:
					$contacts = [ $person ];
					break;
				case self::RECIPIENT_GROUP_CONTACT:
					$contacts = array_filter(
						$this->person_dao->get_all_in_group( $group->id ),
						function ( Person $person ) {
							return $person->is_group_contact && ! empty( trim( $person->email ) );
						} );
					break;
			}
			$this->send_messages( $mt, $group, $contacts );
		} );
	}

	public function send_group_status_change_messages( Group $group ) {
		foreach ( $group->get_status_changes() as $change ) {
			list( $old_status, $new_status ) = $change;

			$event_name = self::group_status_change_event_name( $old_status, $new_status );

			$message_templates = $this->message_template_dao->get_for_event( $group->competition_id, $event_name );

			// TODO: array_walk is pretty much the same in both function
			array_walk( $message_templates, function ( MessageTemplate $mt ) use ( $group ) {
				$contacts = [];
				switch ( $mt->auto_send_recipient ) {
					case self::RECIPIENT_ADMIN:
						$contacts = [ Person::from_email( get_option( 'admin_email' ) ) ];
						break;
					case self::RECIPIENT_GROUP_CONTACT:
						$contacts = array_filter(
							$this->person_dao->get_all_in_group( $group->id ),
							function ( Person $person ) {
								return $person->is_group_contact && ! empty( trim( $person->email ) );
							} );
						break;
				}
				$this->send_messages( $mt, $group, $contacts );
			} );
		}
	}

	private function send_messages(MessageTemplate $mt, Group $group, $contacts) {
		foreach ( $contacts as $contact ) {
			$template_parameters = array_merge(
				Template::site_parameters(),
				Template::person_parameters( $contact ),
				Template::group_parameters( $group )
			);

			$message = $mt->to_message(
				$contact,
				$template_parameters,
				$this->message_sender );

			$message->validate();
			$message->send();
		}

	}
}