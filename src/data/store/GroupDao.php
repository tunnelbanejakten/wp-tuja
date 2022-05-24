<?php

namespace tuja\data\store;

use DateTime;
use DateTimeInterface;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\util\Anonymizer;
use tuja\util\Database;
use tuja\util\fee\GroupFeeCalculator;
use tuja\util\fee\CompetingParticipantFeeCalculator;
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
				'random_id'            => $this->id->random_string(),
				// 'name'                 => $group->name,
				'competition_id'       => $group->competition_id,
				'payment_instructions' => self::serialize_payment_instructions( $group->fee_calculator, array() ),
				'map_id'               => $group->map_id,
				'is_always_editable'   => $group->is_always_editable
			),
			array(
				'%s',
				'%d',
				'%s',
				'%d',
				'%d',
			) );

		$success = $affected_rows === 1;

		if ( ! $success ) {
			throw new Exception($this->wpdb->last_error);
		}

		$group->id = $this->wpdb->insert_id;

		$success = $this->add_record( $group );

		return $success ? $group->id : false;
	}

	function update( Group $group ) {
		$group->validate();

		$success = $this->wpdb->update( $this->table,
			array(
				'is_always_editable'   => $group->is_always_editable,
				'payment_instructions' => self::serialize_payment_instructions( $group->fee_calculator, array() ),
				'map_id'               => $group->map_id
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
				'city'        => $group->city,
				'category_id' => $group->category_id,
				'note'        => $group->note
			),
			array(
				'%d',
				'%d',

				'%s',
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


	function generate_query( $where, DateTimeInterface $date = null, bool $core_data_only = false ) {
		$columns = array(
			'g.*',
			'gp.*',
		);
		if (!$core_data_only) {
			$columns[] = 'gc.payment_instructions AS gc_payment_instructions';
			$columns[] = 'c.payment_instructions AS c_payment_instructions';
		}
		$tables = array(
			$this->table . ' AS g',
			$this->props_table . ' AS gp ON g.id = gp.team_id',
		);
		if (!$core_data_only) {
			$tables[] = Database::get_table( 'competition' ) . ' AS c ON g.competition_id = c.id';
			$tables[] = Database::get_table( 'team_category' ) . ' AS gc ON gp.category_id = gc.id';
		}
		return '
			SELECT
			'.join(', ', $columns).'
			FROM 
				'.join(' INNER JOIN ', $tables).'
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
			$this->generate_query( [ 'g.id = %d' ], $date ),
			$id );
	}

	function get_by_key( $key, $date = null ) {
		return $this->get_object(
			function ( $row ) use ( $date ) {
				return self::to_group( $row, $date );
			},
			$this->generate_query( [ 'g.random_id = %s' ], $date ),
			$key );
	}

	function get_all_in_competition( $competition_id, $include_deleted = false, $date = null ) {
		$objects = $this->get_objects(
			function ( $row ) use ( $date ) {
				return self::to_group( $row, $date );
			},
			$this->generate_query( [ 'g.competition_id = %d' ], $date ),
			$competition_id );

		return $include_deleted ? $objects : array_filter( $objects, function ( Group $group ) {
			return $group->get_status() != Group::STATUS_DELETED;
		} );
	}

	function search( $competition_id, string $query ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_group( $row, null, true );
			},
			$this->generate_query( array( 'g.competition_id = %d', 'gp.name LIKE %s' ), null, true),
			$competition_id,
			"%${query}%",
		);
	}

	private static function to_group( $result, $date, bool $core_data_only = false ): Group {
		$g                     = new Group();
		$g->id                 = isset($result->team_id) ? intval($result->team_id) : null;
		$g->random_id          = $result->random_id;
		$g->name               = $result->name;
		$g->set_status( $result->status );
		if ( ! $core_data_only ) {
			$g->city               = $result->city;
			$g->category_id        = isset($result->category_id) ? intval($result->category_id) : null;
			$g->competition_id     = isset($result->competition_id) ? intval($result->competition_id) : null;
			$g->map_id             = isset($result->map_id) ? intval($result->map_id) : null;
			$g->is_always_editable = $result->is_always_editable;
			$g->note               = $result->note;
			$g->age_competing_avg  = null;

			list ($fee_calculator, )     = self::deserialize_payment_instructions($result->payment_instructions);
			list ($fee_calculator_gc, )  = self::deserialize_payment_instructions($result->gc_payment_instructions);
			list ($fee_calculator_c, )   = self::deserialize_payment_instructions($result->c_payment_instructions);
			$g->fee_calculator           = $fee_calculator;
			$g->effective_fee_calculator = $fee_calculator ?? $fee_calculator_gc ?? $fee_calculator_c ?? new CompetingParticipantFeeCalculator();

			$people                    = ( new PersonDao() )->get_all_in_group( $g->id, false, $date );
			$people_competing          = array_filter( $people, function ( Person $person ) {
				return $person->is_competing();
			} );
			$people_competing_with_age = array_filter( $people, function ( Person $person ) {
				return $person->is_competing() && $person->age > 0;
			} );
	
			$ages = array_map(function ( Person $person ) { return $person->age; }, $people_competing_with_age );
	
			if(count( $people_competing_with_age ) > 0) {
				$g->age_competing_avg = array_sum( $ages ) / count( $people_competing_with_age );
			}
	
			$g->age_competing_min = array_reduce(
				$ages,
				function ( $min, $age ) { return $min === null || $age < $min ? $age : $min; },
				null
			);
			$g->age_competing_max = array_reduce(
				$ages,
				function ( $max, $age ) { return $max === null || $max < $age ? $age : $max; },
				null
			);
	
			$g->count_competing    = count( $people_competing );
			$g->count_follower     = count(
				array_filter( $people, function ( Person $person ) {
					return $person->is_adult_supervisor();
				} ) );
			$g->count_team_contact = count(
				array_filter( $people, function ( Person $person ) {
					return $person->is_contact();
				} ) );
		}

		return $g;
	}

	function delete( $id ) {
		$query_template = 'DELETE FROM ' . $this->table . ' WHERE id = %d';

		$affected_rows = $this->wpdb->query( $this->wpdb->prepare( $query_template, $id ) );

		$success = $affected_rows !== false && $affected_rows === 1;

		if ( ! $success ) {
			throw new Exception($this->wpdb->last_error);
		}
	}

	public function anonymize( array $group_ids = array() ) {
		$anonymizer = new Anonymizer();

		if ( empty( $group_ids ) ) {
			throw new Exception( 'Must specify groups to anonymize.' );
		}

		$props_table = $this->props_table;
		$group_ids   = join( ', ', $group_ids );

		$get_names_query    = "SELECT DISTINCT name FROM $props_table WHERE id IN ($group_ids)";
		$update_names_query = "UPDATE $props_table SET name = %s, city = %s WHERE name = %s AND id IN ($group_ids)";
		
		$current_names = $this->wpdb->get_col( $get_names_query, ARRAY_N );

		foreach ( $current_names as $current_name ) {
			$new_city = $anonymizer->neighborhood();
			$new_name = $anonymizer->animal() . ' från ' . $new_city;

			$this->wpdb->query(
				$this->wpdb->prepare(
				$update_names_query,
				$new_name,
				$new_city,
				$current_name
				)
			);
		}
	}
}