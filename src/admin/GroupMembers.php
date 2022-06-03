<?php

namespace tuja\admin;

use Exception;
use tuja\data\store\PersonDao;
use tuja\data\store\GroupDao;

class GroupMembers extends AbstractGroup {

	public function __construct() {
		parent::__construct();
	}

	public function handle_post() {
		global $wpdb;

		if ( ! isset( $_POST['tuja_points_action'] ) ) {
			return;
		}

		@list( $action, $parameter ) = explode( '__', @$_POST['tuja_points_action'] );

		if ( $action === 'move_people' ) {

			if ( ! isset( $_POST['tuja_group_people'] ) || ! is_array( $_POST['tuja_group_people'] ) ) {
				AdminUtils::printError( 'No people choosen.' );

				return;
			}

			if ( ! isset( $_POST['tuja_group_move_people_to'] ) || ! is_numeric( $_POST['tuja_group_move_people_to'] ) ) {
				AdminUtils::printError( 'No group choosen.' );

				return;
			}

			$move_to_group = $this->group_dao->get( intval( $_POST['tuja_group_move_people_to'] ) );

			if ( ! isset( $_POST['tuja_group_people'] ) || ! is_array( $_POST['tuja_group_people'] ) || $move_to_group === false ) {
				AdminUtils::printError( 'No people choosen.' );

				return;
			}

			foreach ( $_POST['tuja_group_people'] as $person_id ) {
				$person_dao       = new PersonDao();
				$person           = $person_dao->get( $person_id );
				$person->group_id = $move_to_group->id;
				try {
					$affected_rows = $person_dao->update( $person );
					if ( $affected_rows === false ) {
						AdminUtils::printError( sprintf( 'Could not move %s to %s.', $person->name, $move_to_group->name ) );
					}
				} catch ( Exception $e ) {
					AdminUtils::printException( $e );
				}
			}
		}
	}

	public function output() {
		$this->handle_post();

		$group       = $this->group;
		$competition = $this->competition;

		$person_dao = new PersonDao();
		$people     = $person_dao->get_all_in_group( $group->id, true );

		$db_groups = new GroupDao();
		$groups    = $db_groups->get_all_in_competition( $competition->id, true );

		$add_member_url = add_query_arg(
			array(
				'tuja_competition' => $this->competition->id,
				'tuja_view'        => 'GroupMember',
				'tuja_group'       => $this->group->id,
				'tuja_person'      => 'new',
			)
		);

		include 'views/group-members.php';
	}
}
