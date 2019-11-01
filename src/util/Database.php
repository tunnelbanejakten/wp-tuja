<?php

namespace tuja\util;

use DateTime;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\Plugin;

class Database {

	static public function get_table($name) {
		global $wpdb;

		return $wpdb->prefix . Plugin::TABLE_PREFIX . $name;
	}


	static public function add_foreign_key( $table, $key, $references, $on_delete = 'RESTRICT' ) {
		global $wpdb;

		$con_name   = self::get_constraint_name( $table, $key, $references );
		$table      = self::get_table( $table );
		$references = self::get_table( $references );

		$check  = $wpdb->prepare( 'SELECT DISTINCT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = %s AND CONSTRAINT_NAME = %s', $wpdb->dbname, $con_name );
		$exists = $wpdb->get_var( $check );

		if ( ! $exists ) {
			$query = "ALTER TABLE $table ADD FOREIGN KEY $con_name ($key) REFERENCES $references (id) ON DELETE $on_delete";

			$wpdb->get_var( $query );
			$error = $wpdb->last_error;

			if ( $error ) {
				throw new Exception( "Unable to create foreign key $table.$key. $error" );
			}
		}

		return true;
	}


	/**
	 * Get constraint name which is unique, predictable and not longer than 64 characters
	 */
	private static function get_constraint_name( $table, $key, $references ) {
		$con_name = $table . '_' . $key . '_' . $references . '_id';
		if ( strlen( $con_name ) > 64 ) {
			// Too long name. Truncate name and use hash of name to make the truncated name unique and predictable.
			$hash     = hash( 'crc32', $con_name );
			$con_name = substr( $con_name, 0, 64 - strlen( $hash ) ) . $hash;
		}

		return $con_name;
	}


	static public function start_transaction() {
		global $wpdb;
		$wpdb->query('START TRANSACTION');
	}


	static public function commit() {
		global $wpdb;
		$wpdb->query('COMMIT');
	}

	static public function rollback() {
		global $wpdb;
		$wpdb->query('ROLLBACK');
	}
}