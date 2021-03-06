<?php

namespace tuja\data\store;

use DateTime;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\util\Anonymizer;
use tuja\util\Database;
use tuja\util\messaging\EventMessageSender;
use tuja\util\rules\RuleResult;

class GroupDao extends AbstractDao {
	private $props_table;

	function __construct() {
		parent::__construct();
		$this->table       = Database::get_table( 'team' );
		$this->props_table = Database::get_table( 'team_properties' );
	}

	function create( Group $group ) {
		$group->set_status( Group::DEFAULT_STATUS );

		$group->validate();

		$affected_rows = $this->wpdb->insert( $this->table,
			array(
				'random_id'          => $this->id->random_string(),
				'competition_id'     => $group->competition_id,
				'is_always_editable' => $group->is_always_editable
			),
			array(
				'%s',
				'%d',
				'%d',
			) );

		$success = $affected_rows !== false && $affected_rows === 1;

		if ( ! $success ) {
			return false;
		}

		$group->id = $this->wpdb->insert_id;

		$success = $this->add_record( $group );

		return $success ? $group->id : false;
	}

	function update( Group $group ) {
		$group->validate();

		$success = $this->wpdb->update( $this->table,
			array(
				'is_always_editable' => $group->is_always_editable
			),
			array(
				'id' => $group->id
			) );

		if ( $success !== false ) {
			$success = $this->add_record( $group );
		}

		return $success;
	}

	/**
	 * Evaluate the group's registration and change the group's status if necessary.
	 *
	 * @param Group $group
	 */
	function run_registration_rules( Group $group ) {
		$current_group_status = $group->get_status();
		if ( $current_group_status == Group::STATUS_INCOMPLETE_DATA || $current_group_status == Group::STATUS_ACCEPTED ) {

			$evaluation_result   = $group->evaluate_registration();
			$registration_errors = array_filter( $evaluation_result, function ( RuleResult $rule_result ) {
				return $rule_result->status == RuleResult::BLOCKER;
			} );

			$is_complete_registration = count( $registration_errors ) == 0;
			try {
				if ( ! $is_complete_registration && $current_group_status == Group::STATUS_ACCEPTED ) {
					$group->set_status( Group::STATUS_INCOMPLETE_DATA );
					$this->add_record( $group );
				} elseif ( $is_complete_registration && $current_group_status == Group::STATUS_INCOMPLETE_DATA ) {
					$group->set_status( Group::STATUS_ACCEPTED );
					$this->add_record( $group );
				}
			} catch ( ValidationException $e ) {
			}
		}
	}

	private function add_record( Group $group ) {
		$affected_rows = $this->wpdb->insert( $this->props_table,
			array(
				'team_id'    => $group->id,
				'created_at' => self::to_db_date( new DateTime() ),

				'status'      => $group->get_status(),
				'name'        => $group->name,
				'category_id' => $group->category_id,
				'note'        => $group->note
			),
			array(
				'%d',
				'%d',

				'%s',
				'%s',
				'%d',
				'%s'
			) );

		$success = $affected_rows !== false && $affected_rows === 1;

		if ( $success ) {
			try {
				$change_message_sender = new EventMessageSender();
				$change_message_sender->send_group_status_change_messages( $group );

				// Empty the list of status changes so that we don't sent the same message twice
				// if/when add_record is called multiple times during a page load.
				$group->clear_status_changes();
			} catch ( Exception $e ) {
				var_dump( $e );
			}
		}

		return $success;
	}


	function generate_query( $where, DateTime $date = null ) {
		return '
			SELECT
				g.*,
				gp.*
			FROM 
				' . $this->table . ' AS g 
				INNER JOIN 
				' . $this->props_table . ' AS gp 
				ON g.id = gp.team_id 
			WHERE 
				gp.id IN (
					SELECT MAX(id)
					FROM ' . $this->props_table . '
					WHERE created_at <= ' . self::to_db_date( $date ?: new DateTime() ) . '
					GROUP BY team_id
				)
				AND ' . join( ' AND ', $where ) . ' 
			ORDER BY 
				gp.name';
	}

	function get( $id, $date = null ) {
		return $this->get_object(
			function ( $row ) use ( $date ) {
				return self::to_group( $row, $date );
			},
			$this->generate_query( [ 'g.id = %d' ] ),
			$id );
	}

	function get_by_key( $key, $date = null ) {
		return $this->get_object(
			function ( $row ) use ( $date ) {
				return self::to_group( $row, $date );
			},
			$this->generate_query( [ 'g.random_id = %s' ] ),
			$key );
	}

	function get_all_in_competition( $competition_id, $include_deleted = false, $date = null ) {
		$objects = $this->get_objects(
			function ( $row ) use ( $date ) {
				return self::to_group( $row, $date );
			},
			$this->generate_query( [ 'g.competition_id = %d' ] ),
			$competition_id );

		return $include_deleted ? $objects : array_filter( $objects, function ( Group $group ) {
			return $group->get_status() != Group::STATUS_DELETED;
		} );
	}

	private static function to_group( $result, $date ): Group {
		$g                     = new Group();
		$g->id                 = $result->team_id;
		$g->random_id          = $result->random_id;
		$g->name               = $result->name;
		$g->category_id        = $result->category_id;
		$g->competition_id     = $result->competition_id;
		$g->is_always_editable = $result->is_always_editable;
		$g->note               = $result->note;
		$g->set_status( $result->status );

		$people                    = ( new PersonDao() )->get_all_in_group( $g->id, false, $date );
		$people_competing          = array_filter( $people, function ( Person $person ) {
			return $person->is_competing();
		} );
		$people_competing_with_age = array_filter( $people, function ( Person $person ) {
			return $person->is_competing() && $person->age > 0;
		} );
		$g->age_competing_avg      = count( $people_competing_with_age ) > 0 ? array_sum(
			                                                                       array_map(
				                                                                       function ( Person $person ) {
					                                                                       return $person->age;
				                                                                       },
				                                                                       $people_competing_with_age ) )
		                                                                       / count( $people_competing_with_age ) : null;
		$g->age_competing_min      = array_reduce(
			array_map(
				function ( Person $person ) {
					return $person->age;
				},
				$people_competing_with_age ),
			function ( $min, $age ) {
				return $min === null || $age < $min ? $age : $min;
			},
			null );
		$g->age_competing_max      = array_reduce(
			array_map(
				function ( Person $person ) {
					return $person->age;
				},
				$people_competing_with_age ),
			function ( $max, $age ) {
				return $max === null || $max < $age ? $age : $max;
			},
			null );

		$g->count_competing    = count( $people_competing );
		$g->count_follower     = count(
			array_filter( $people, function ( Person $person ) {
				return $person->is_adult_supervisor();
			} ) );
		$g->count_team_contact = count(
			array_filter( $people, function ( Person $person ) {
				return $person->is_contact();
			} ) );

		return $g;
	}

	public function anonymize( array $group_ids = [] ) {
		$anonymizer = new Anonymizer();

		if ( empty( $group_ids ) ) {
			throw new Exception( 'Must specify groups to anonymize.' );
		}

		$where = 'id IN (' . join( ', ', $group_ids ) . ')';

		$current_names = array_map( function ( $row ) {
			return $row[0];
		}, $this->wpdb->get_results( $this->wpdb->prepare( 'SELECT DISTINCT name FROM ' . $this->table . ' WHERE ' . $where ), ARRAY_N ) );
		foreach ( $current_names as $current_name ) {
			$this->wpdb->query( $this->wpdb->prepare(
				'UPDATE ' . $this->table . ' SET name = %s WHERE name = %s AND ' . $where,
				$anonymizer->animal() . ' från ' . $anonymizer->neighborhood(),
				$current_name ) );
		}
	}
}