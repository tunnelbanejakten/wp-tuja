<?php

namespace tuja\data\store;

use Exception;
use tuja\data\model\ValidationException;
use tuja\data\model\Group;
use tuja\util\Anonymizer;
use tuja\util\Database;

class GroupDao extends AbstractDao {
	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'team' );
	}

	function create( Group $group ) {
		$group->validate();

		if ( $this->exists( $group ) ) {
			throw new ValidationException( 'name', 'Det finns redan ett lag med detta namn.' );
		}

		$affected_rows = $this->wpdb->insert( $this->table,
			array(
				'random_id'      => $this->id->random_string(),
				'competition_id' => $group->competition_id,
				'name'           => $group->name,
				'type'           => '',
				'category_id'    => $group->category_id
			),
			array(
				'%s',
				'%d',
				'%s',
				'%s',
				'%d'
			) );
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	function update( Group $group ) {
		$group->validate();

		if ( $this->exists( $group ) ) {
			throw new ValidationException( 'name', 'Det finns redan ett lag med detta namn.' );
		}

		return $this->wpdb->update( $this->table,
			array(
				'name'        => $group->name,
				'category_id' => $group->category_id
			),
			array(
				'id' => $group->id
			) );
	}


	function generate_query( $where ) {
		$age_subquery = function ( $fn, $column_name ) {
			return '(SELECT ' . $fn . '(DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(pno, 8), \'%%Y%%m%%d\')) / 365.25)' .
			       ' FROM wp_tuja_person' .
			       ' WHERE team_id = g.id' .
			       ' AND is_competing = TRUE) AS ' . $column_name;
		};

		return 'SELECT' .
		       '  g.*,' .
		       '  ' . $age_subquery( 'avg', 'age_competing_avg' ) . ', ' .
		       '  ' . $age_subquery( 'stddev_pop', 'age_competing_stddev' ) . ', ' .
		       '  ' . $age_subquery( 'min', 'age_competing_min' ) . ', ' .
		       '  ' . $age_subquery( 'max', 'age_competing_max' ) . ', ' .
		       '  (select count(*) from wp_tuja_person where team_id = g.id and is_competing = true) as count_competing,' .
		       '  (select count(*) from wp_tuja_person where team_id = g.id and is_competing = false) as count_follower,' .
		       '  (select count(*) from wp_tuja_person where team_id = g.id and is_team_contact = true) as count_team_contact ' .
		       'FROM ' .
		       '  ' . $this->table . ' AS g ' .
		       'WHERE ' .
		       '  ' . join( ' AND ', $where ) . ' ' .
		       'ORDER BY ' .
		       '  g.name';
	}

	function exists( $group ) {
		$db_results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT id FROM ' . $this->table . ' WHERE name = %s AND id != %d AND competition_id = %d',
				$group->name,
				$group->id,
				$group->competition_id
			),
			OBJECT
		);

		return $db_results !== false && count( $db_results ) > 0;
	}

	function get( $id ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_group( $row );
			},
			$this->generate_query( [ 'g.id = %d' ] ),
			$id );
	}

	function get_by_key( $key ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_group( $row );
			},
			$this->generate_query( [ 'g.random_id = %s' ] ),
			$key );
	}

	function get_all_in_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_group( $row );
			},
			$this->generate_query( [ 'g.competition_id = %d' ] ),
			$competition_id );
	}

	private static function to_group( $result ): Group {
		$g                       = new Group();
		$g->id                   = $result->id;
		$g->random_id            = $result->random_id;
		$g->name                 = $result->name;
		$g->category_id          = $result->category_id;
		$g->competition_id       = $result->competition_id;
		$g->age_competing_avg    = $result->age_competing_avg;
		$g->age_competing_stddev = $result->age_competing_stddev;
		$g->age_competing_min    = $result->age_competing_min;
		$g->age_competing_max    = $result->age_competing_max;
		$g->count_competing      = $result->count_competing;
		$g->count_follower       = $result->count_follower;
		$g->count_team_contact   = $result->count_team_contact;

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