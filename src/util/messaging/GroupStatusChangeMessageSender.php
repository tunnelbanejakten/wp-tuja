<?php

namespace tuja\util\messaging;


use tuja\data\model\Group;
use tuja\data\model\MessageTemplate;
use tuja\data\model\Person;
use tuja\data\store\MessageTemplateDao;
use tuja\data\store\PersonDao;
use tuja\util\Template;

class GroupStatusChangeMessageSender {

	private $message_sender;
	private $person_dao;
	private $message_template_dao;

	public function __construct() {
		$this->message_template_dao = new MessageTemplateDao();
		$this->person_dao           = new PersonDao();
		$this->message_sender       = new MessageSender();
	}

	/*	public function send_status_change_messages( Group $group ) {

			foreach ( $group->get_status_changes() as $change ) {
				list( $old_status, $new_status ) = $change;

				$event_name        = join( '.', [ 'group_status', $old_status, $new_status, 'group_contact' ] );
				$message_templates = $this->message_template_dao->get_for_competition_event( $group->competition_id, $event_name );

				$contacts = array_filter( $this->person_dao->get_all_in_group( $group->id ), function ( Person $person ) {
					return $person->is_group_contact && ! empty( trim( $person->email ) );
				} );

				foreach ( $message_templates as $message_template ) {

					foreach ( $contacts as $contact ) {
						$message = new OutgoingEmailMessage(
							$this->message_sender,
							$contact->email,
							$message_template->body,
							$message_template->subject );
						$message->send();
					}
				}
			}

		}*/

	public function send_status_change_messages( Group $group ) {

		foreach ( $group->get_status_changes() as $change ) {
			list( $old_status, $new_status ) = $change;

			$subject_file_name = join( '.', [ 'group_status', $old_status, $new_status, 'subject.txt' ] );
			$body_file_name    = join( '.', [ 'group_status', $old_status, $new_status, 'body.md' ] );

			$mt          = new MessageTemplate();
			$mt->subject = file_get_contents( __DIR__ . '/' . $subject_file_name );
			$mt->body    = file_get_contents( __DIR__ . '/' . $body_file_name );

			if ( $mt->subject === false || $mt->body === false ) {
				continue;
			}

			$message_templates = [ $mt ];

			$contacts = array_filter(
				$this->person_dao->get_all_in_group( $group->id ),
				function ( Person $person ) {
					return $person->is_group_contact && ! empty( trim( $person->email ) );
				} );

			foreach ( $message_templates as $message_template ) {

				foreach ( $contacts as $contact ) {

					$template_parameters = array_merge(
						Template::site_parameters(),
						Template::person_parameters( $contact ),
						Template::group_parameters( $group )
					);
					$message             = new OutgoingEmailMessage(
						$this->message_sender,
						$contact,
						Template::string( $message_template->body )->render( $template_parameters, true ),
						Template::string( $message_template->subject )->render( $template_parameters ) );

					$message->validate();
					$message->send();
				}
			}
		}
	}
}