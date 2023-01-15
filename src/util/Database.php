<?php

namespace tuja\util;

use DateTime;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\Plugin;

class Database {

	static public function get_table( $name ) {
		global $wpdb;

		return $wpdb->prefix . Plugin::TABLE_PREFIX . $name;
	}

	static public function update_foreign_keys( $keys ) {
		global $wpdb;

		//
		// Get information about which foreign keys SHOULD exists.
		//

		$expected_constraints = array_combine(
			array_map(
				function ( $values ) {
					list ($table, $key, $references, $on_delete) = $values;
					return self::get_constraint_name( $table, $key, $references );
				},
				$keys
			),
			$keys
		);

		//
		// Get information about which foreign keys ACTUALLY exist.
		//

		$stmt                 = $wpdb->prepare(
			'
			SELECT CONSTRAINT_NAME AS constraint_name, TABLE_NAME as table_name, DELETE_RULE AS delete_rule
			FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS 
			WHERE CONSTRAINT_SCHEMA = %s AND TABLE_NAME LIKE %s
			',
			$wpdb->dbname,
			self::get_table( '' ) . '%'
		);
		$db_results           = $wpdb->get_results( $stmt, OBJECT );
		$existing_constraints = array_combine(
			array_map(
				function ( $values ) {
					return $values->constraint_name;
				},
				$db_results
			),
			array_map(
				function ( $values ) {
					return array(
						$values->table_name,
						null,
						null,
						$values->delete_rule,
					);
				},
				$db_results
			)
		);

		$queries = array();

		//
		// Generate queries to ADD missing foreign keys.
		//

		$constraints_to_add = array_diff_key( $expected_constraints, $existing_constraints );
		foreach ( $constraints_to_add as $constraint_name => $values ) {
			list ($table, $key, $references, $on_delete) = $values;

			$table      = self::get_table( $table );
			$references = self::get_table( $references );
			$queries[]  = "ALTER TABLE $table ADD CONSTRAINT $constraint_name FOREIGN KEY $constraint_name ($key) REFERENCES $references (id) ON DELETE $on_delete";
		}

		//
		// Generate queries to UPDATE foreign keys which have wrong "on-delete action".
		//

		$constraints_to_alter = array_filter(
			array_intersect_key( $expected_constraints, $existing_constraints ),
			function ( $values, $constraint_name ) use ( $existing_constraints ) {
				list ($table, $key, $references, $on_delete) = $values;
				return $existing_constraints[ $constraint_name ][3] !== $on_delete;
			},
			ARRAY_FILTER_USE_BOTH
		);
		foreach ( $constraints_to_alter as $constraint_name => $values ) {
			list ($table, $key, $references, $on_delete) = $values;

			$table      = self::get_table( $table );
			$references = self::get_table( $references );
			$queries[]  = "ALTER TABLE $table DROP FOREIGN KEY $constraint_name";
			$queries[]  = "ALTER TABLE $table ADD CONSTRAINT $constraint_name FOREIGN KEY $constraint_name ($key) REFERENCES $references (id) ON DELETE $on_delete";
		}

		//
		// Generate queries to DELETE foreign keys which are no longer needed.
		//

		$constraints_to_drop = array_diff_key( $existing_constraints, $expected_constraints );
		foreach ( $constraints_to_drop as $constraint_name => $values ) {
			list ($table, $key, $references, $on_delete) = $values;

			$queries[] = "ALTER TABLE $table DROP FOREIGN KEY $constraint_name";
		}

		//
		// Run necessary migrations:
		//

		foreach ( $queries as $query ) {
			error_log( "Update foreign keys with this SQL query: $query" );
			$wpdb->get_var( $query );
			$error = $wpdb->last_error;

			if ( $error ) {
				throw new Exception( "Failed to update foreign keys with this SQL query: $query. $error" );
			}
		}
	}

	/**
	 * Get constraint name which is unique, predictable and not longer than 64 characters
	 */
	private static function get_constraint_name( $table, $key, $references ) {
		$constraint_name = $table . '_' . $key . '_' . $references . '_id';
		if ( strlen( $constraint_name ) > 64 ) {
			// Too long name. Truncate name and use hash of name to make the truncated name unique and predictable.
			$hash            = hash( 'crc32', $constraint_name );
			$constraint_name = substr( $constraint_name, 0, 64 - strlen( $hash ) ) . $hash;
		}

		return $constraint_name;
	}


	static public function start_transaction() {
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
	}


	static public function commit() {
		global $wpdb;
		$wpdb->query( 'COMMIT' );
	}

	static public function rollback() {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
	}
}
