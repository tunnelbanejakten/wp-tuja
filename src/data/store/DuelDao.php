<?php

namespace tuja\data\store;

use DateTime;
use Error;
use tuja\data\model\Duel;
use tuja\data\model\DuelGroup;
use tuja\data\model\DuelInvite;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\util\Database;

class DuelDao extends AbstractDao {

	private $table_duel_group;
	private $table_duel;
	private $table_duel_invite;

	function __construct() {
		parent::__construct();
		$this->table_duel_group  = Database::get_table( 'duel_group' );
		$this->table_duel        = Database::get_table( 'duel' );
		$this->table_duel_invite = Database::get_table( 'duel_invite' );
	}

	public function create_duel_group( DuelGroup $duel_group ) {
		$affected_rows = $this->wpdb->insert(
			$this->table_duel_group,
			array(
				'competition_id'        => $duel_group->competition_id,
				'name'                  => $duel_group->name,
				'link_form_question_id' => $duel_group->link_form_question_id,
				'created_at'            => self::to_db_date( new DateTime() ),
			),
			array(
				'%d',
				'%s',
				'%d',
				'%d',
			)
		);
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;

	}

	public function create_duel( Duel $duel ) {
		$affected_rows = $this->wpdb->insert(
			$this->table_duel,
			array(
				'random_id'     => $this->id->random_string(),
				'duel_group_id' => intval( $duel->duel_group_id ),
				// 'name'          => $duel->name,
				'display_at'    => null,
				'duel_at'       => null,
				'created_at'    => self::to_db_date( new DateTime() ),
			),
			array(
				'%s',
				'%d',
				// '%s',
				'%d',
				'%d',
				'%d',
			)
		);
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	public function create_invite( DuelInvite $invite ) {
		$affected_rows = $this->wpdb->insert(
			$this->table_duel_invite,
			array(
				'duel_id'           => intval( $invite->duel_id ),
				'team_id'           => intval( $invite->team_id ),
				'random_id'         => $this->id->random_string(),
				'status'            => DuelInvite::STATUS_PENDING,
				'status_updated_at' => self::to_db_date( new DateTime() ),
			),
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%d',
			)
		);
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	public function delete_duel_group( $duel_group_id ) {
		$query_template = 'DELETE FROM ' . $this->table_duel_group . ' WHERE id = %d';

		return $this->wpdb->query( $this->wpdb->prepare( $query_template, $duel_group_id ) );
	}

	public function update_duel_group( $name ) {
		throw new Error( 'Not implemented' );
	}

	// Cancel any duel invites for specified duel group for specified groups.
	// Create new duels with min_duel_participant_count or min_duel_participant_count+1 groups.
	// Groups are selected randomly from list of group_ids.
	public function create_duels( int $duel_group, array $group_ids, int $min_duel_participant_count ) {
		$this->bulk_cancel_invites( $duel_group, $group_ids );
		$this->bulk_create_invites( $duel_group, $group_ids, $min_duel_participant_count );
	}

	public function bulk_create_invites( int $duel_group, array $group_ids, int $min_duel_participant_count ) {
		$duels_group_ids = array();
		// var_dump( $group_ids );
		shuffle( $group_ids );
		// var_dump( $group_ids );
		while ( count( $group_ids ) >= $min_duel_participant_count ) {
			$duels_group_ids[] = array_splice( $group_ids, 0, $min_duel_participant_count, array() );
		}

		$i = 0;
		while ( count( $group_ids ) > 0 ) {
			$duels_group_ids[ $i++ ][] = array_pop( $group_ids );
		}

		// var_dump( $duels_group_ids );

		foreach ( $duels_group_ids as $group_ids ) {
			// Create duel.
			$duel                = new Duel();
			$duel->duel_group_id = $duel_group;
			$duel_id             = $this->create_duel( $duel );
			// Invite groups in $group_ids.
			foreach ( $group_ids as $group_id ) {
				$invite          = new DuelInvite();
				$invite->duel_id = $duel_id;
				$invite->team_id = $group_id;
				$invite_id       = $this->create_invite( $invite );
			}
		}
	}

	private function bulk_cancel_invites( int $duel_group, array $group_ids ) {
		$group_ids_string = join( ', ', array_map( 'intval', array_filter( $group_ids, 'is_numeric' ) ) );
		$query_template   = '
				UPDATE ' . $this->table_duel_invite . ' 
				SET status = %s, status_updated_at = %d
				WHERE status != %s AND duel_id IN (SELECT id FROM ' . $this->table_duel . ' WHERE duel_group_id = %d) AND team_id IN (' . $group_ids_string . ')';
		$query            = $this->wpdb->prepare(
			$query_template,
			DuelInvite::STATUS_CANCELLED,
			self::to_db_date( new DateTime() ),
			DuelInvite::STATUS_CANCELLED,
			$duel_group
		);

		return $this->wpdb->query( $query );
	}

	// More used during development or to fix bad state.
	public function remove_duel_invite( int $duel_id, int $group_id ) {
			$query_template = 'DELETE FROM ' . $this->table_duel_invite . ' WHERE duel_id = %d AND team_id = %d';

		return $this->wpdb->query( $this->wpdb->prepare( $query_template, $duel_id, $group_id ) );
	}

	public function set_duel_invite_status( int $duel_id, int $group_id ) {
		$query_template = '
				UPDATE ' . $this->table_duel_invite . ' 
				SET status = %s, status_updated_at = %d
				WHERE status != %s AND duel_id = %d AND team_id = %d';
		$query          = $this->wpdb->prepare(
			$query_template,
			$new_status,
			self::to_db_date( new DateTime() ),
			$new_status,
			$duel_id,
			$group_id
		);

		return $this->wpdb->query( $query );
	}

	// Return nested structure:
	// - DuelGroup (name)
	//   - Duel (time)
	//     - DuelInvite (team name)
	//       - Contact
	public function get_duels_by_group( Group $group ) {
		$duels                   = $this->get_objects(
			function ( $row ) {
				return array(
					'id'         => intval( $row->id ),
					'name'       => $row->name,
					'duel_at'    => self::from_db_date( $row->duel_at ),
					'display_at' => self::from_db_date( $row->display_at ),
				);
			},
			'
			SELECT 
				dg.name, d.duel_at, d.display_at, d.id 
			FROM 
			' . $this->table_duel_invite . ' AS di 
			INNER JOIN ' . $this->table_duel . ' AS d ON di.duel_id = d.id
			INNER JOIN ' . $this->table_duel_group . ' AS dg ON d.duel_group_id = dg.id
			WHERE di.team_id = %d AND di.status != %s',
			$group->id,
			DuelInvite::STATUS_CANCELLED
		);
		$duel_ids_string         = join(
			',',
			array_map(
				function ( $row ) {
					return $row['id'];
				},
				$duels
			)
		);
		$duel_opponent_group_ids = $this->get_objects(
			function ( $row ) {
				return array(
					'duel_id' => intval( $row->duel_id ),
					'team_id' => intval( $row->team_id ),
				);
			},
			'
			SELECT di.duel_id, di.team_id
			FROM ' . $this->table_duel_invite . ' AS di
			WHERE di.duel_id IN (' . $duel_ids_string . ') AND di.team_id != %d
			',
			$group->id
		);
		$person_dao              = new PersonDao();
		$group_dao               = new GroupDao();

		$result = array();
		foreach ( $duels as $duel ) {
			$duel_opponents = array_values(array_filter(
				$duel_opponent_group_ids,
				function ( $data ) use ( $duel ) {
					return $data['duel_id'] === $duel['id'];
				}
			));
			$duel_data = array(
				'name'      => $duel['name'],
				'opponents' => array_map(
					function ( $data ) use ( $group_dao, $person_dao ) {
						$group    = $group_dao->get( $data['team_id'], null, true );
						$contacts = array_filter(
							$person_dao->get_all_in_group( $data['team_id'] ),
							function ( Person $person ) {
								return $person->is_contact() && ( $person->is_competing() || $person->is_adult_supervisor() );
							}
						);
						return array(
							'group_name' => $group->name,
							'contacts'   => array_map(
								function ( Person $contact ) {
									return array(
										'name'  => $contact->name,
										'phone' => $contact->phone,
										'email' => $contact->email,
									);
								},
								$contacts
							),
						);

					},
					$duel_opponents
				),
			);
			$result[]  = $duel_data;
		}

		return $result;
	}

	// Return nested structure:
	// - DuelGroup (name)
	//   - Duel (time)
	//     - DuelInvite (team name)
	public function get_duels_by_competition( $competition_id ) {

		$duel_groups = $this->get_objects(
			function ( $row ) {
				return self::to_duel_group( $row );
			},
			'SELECT * FROM ' . $this->table_duel_group . ' WHERE competition_id = %d',
			$competition_id
		);

		$duels = $this->get_objects(
			function ( $row ) {
				return self::to_duel( $row );
			},
			'SELECT * FROM ' . $this->table_duel . ' WHERE duel_group_id IN (SELECT id FROM ' . $this->table_duel_group . ' WHERE competition_id = %d)',
			$competition_id
		);

		$duel_ids_string = join(
			',',
			array_map(
				function ( Duel $duel ) {
					return $duel->id;
				},
				$duels
			)
		);
		$invites         = $this->get_objects(
			function ( $row ) {
				return self::to_duel_invite( $row );
			},
			'SELECT * FROM ' . $this->table_duel_invite . ' WHERE duel_id IN (' . $duel_ids_string . ') AND status != %s',
			DuelInvite::STATUS_CANCELLED
		);

		foreach ( $duel_groups as $duel_group ) {
			$duel_group->duels = array_filter(
				$duels,
				function ( Duel $duel ) use ( $duel_group ) {
					return $duel->duel_group_id === $duel_group->id;
				}
			);
		}

		foreach ( $duels as $duel ) {
			$duel->invites = array_filter(
				$invites,
				function ( DuelInvite $invite ) use ( $duel ) {
					return $invite->duel_id === $duel->id;
				}
			);
		}

		$group_dao = new GroupDao();
		$groups    = $group_dao->get_all_in_competition( $competition_id );
		foreach ( $invites as $invite ) {
			$invite->group = current(
				array_filter(
					$groups,
					function ( Group $group ) use ( $invite ) {
						return $group->id === $invite->team_id;
					}
				)
			);
		}

		return $duel_groups;
	}

	private static function to_duel_group( $result ): DuelGroup {
		$dg                        = new DuelGroup();
		$dg->id                    = intval( $result->id );
		$dg->competition_id        = intval( $result->competition_id );
		$dg->name                  = $result->name;
		$dg->link_form_question_id = isset( $result->link_form_question_id ) ? intval( $result->link_form_question_id ) : null;
		$dg->created_at            = $result->created_at;

		return $dg;
	}

	private static function to_duel( $result ): Duel {
		$duel                = new Duel();
		$duel->id            = intval( $result->id );
		$duel->random_id     = $result->random_id;
		$duel->duel_group_id = intval( $result->duel_group_id );
		// $duel->name          = $result->name;
		$duel->display_at = self::from_db_date( $result->display_at );
		$duel->duel_at    = self::from_db_date( $result->duel_at );
		$duel->created_at = self::from_db_date( $result->created_at );
		return $duel;
	}

	private static function to_duel_invite( $result ): DuelInvite {
		$invite                    = new DuelInvite();
		$invite->duel_id           = intval( $result->duel_id );
		$invite->team_id           = intval( $result->team_id );
		$invite->random_id         = $result->random_id;
		$invite->status            = $result->status;
		$invite->status_updated_at = self::from_db_date( $result->status_updated_at );
		return $invite;
	}
}
