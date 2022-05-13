<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Event;
use tuja\data\store\EventDao;

class GroupEvents extends AbstractGroup {

	public function __construct() {
		parent::__construct();
	}

	public function handle_post() {
		global $wpdb;

		if ( ! isset( $_POST['tuja_points_action'] ) ) {
			return;
		}

		@list( $action, $parameter ) = explode( '__', @$_POST['tuja_points_action'] );

		if ( $action === 'delete_event' ) {
			$db_event      = new EventDao();
			$affected_rows = $db_event->delete( $parameter );

			$success = $affected_rows !== false && $affected_rows === 1;

			if ( $success ) {
				AdminUtils::printSuccess( 'Händelsen att frågan har visats har tagits bort' );
			} else {
				AdminUtils::printError( 'Kunde inte ta bort händelsen.' );
				if ( $error = $wpdb->last_error ) {
					AdminUtils::printError( $error );
				}
			}
		}
	}
	public function output() {
		$this->handle_post();

		$group       = $this->group;
		$competition = $this->competition;

		$db_event          = new EventDao();

		$view_question_events = array_filter(
			$db_event->get_by_group( $group->id ),
			function ( Event $event ) {
				return $event->event_name === Event::EVENT_VIEW && $event->object_type === Event::OBJECT_TYPE_QUESTION;
			}
		);

		include 'views/group-events.php';
	}
}
