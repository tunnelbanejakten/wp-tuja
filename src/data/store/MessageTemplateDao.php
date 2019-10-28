<?php

namespace tuja\data\store;

use tuja\data\model\ValidationException;
use tuja\data\model\MessageTemplate;
use tuja\util\Database;

class MessageTemplateDao extends AbstractDao
{
	function __construct() {
		parent::__construct();
		$this->table = Database::get_table('message_template');
	}

	function create( MessageTemplate $message_template ) {
		$message_template->validate();

		if ( $this->exists( $message_template->name, $message_template->id ) ) {
			throw new ValidationException( 'name', 'Det finns redan en mall med detta namn.' );
		}

		$affected_rows = $this->wpdb->insert( $this->table,
			array(
				'competition_id'      => $message_template->competition_id,
				'delivery_method'     => $message_template->delivery_method,
				'name'                => $message_template->name,
				'subject'             => $message_template->subject,
				'body'                => $message_template->body,
				'auto_send_recipient' => $message_template->auto_send_recipient,
				'auto_send_trigger'   => $message_template->auto_send_trigger
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s'
			) );
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	function update( MessageTemplate $message_template ) {
		$message_template->validate();

		if ( $this->exists( $message_template->name, $message_template->id ) ) {
			throw new ValidationException( 'name', 'Det finns redan en mall med detta namn.' );
		}

		return $this->wpdb->update( $this->table,
			array(
				'delivery_method'     => $message_template->delivery_method,
				'name'                => $message_template->name,
				'subject'             => $message_template->subject,
				'body'                => $message_template->body,
				'auto_send_recipient' => $message_template->auto_send_recipient,
				'auto_send_trigger'   => $message_template->auto_send_trigger
			),
			array(
				'id' => $message_template->id
			) );
	}

	function exists( $name, $exclude_message_template_id ) {
		$db_results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT id FROM ' . $this->table . ' WHERE name = %s AND id != %d',
				$name,
				$exclude_message_template_id ),
			OBJECT );

		return $db_results !== false && count( $db_results ) > 0;
	}

	function get_all_in_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_message_template( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE competition_id = %d',
			$competition_id );
	}

	function get( $id ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_message_template( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE id = %d',
			$id );
	}

	function get_for_event( $competition_id, string $trigger ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_message_template( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE competition_id = %d AND trigger = %s',
			$competition_id,
			$trigger );
	}

	function delete( $id ) {
		$query_template = 'DELETE FROM ' . $this->table . ' WHERE id = %d';

		return $this->wpdb->query( $this->wpdb->prepare( $query_template, $id ) );
	}

	private static function to_message_template( $result ): MessageTemplate {
		$mt                      = new MessageTemplate();
		$mt->id                  = $result->id;
		$mt->competition_id      = $result->competition_id;
		$mt->delivery_method     = $result->delivery_method;
		$mt->name                = $result->name;
		$mt->subject             = $result->subject;
		$mt->body                = $result->body;
		$mt->auto_send_recipient = $result->auto_send_recipient;
		$mt->auto_send_trigger   = $result->auto_send_trigger;

		return $mt;
	}

}