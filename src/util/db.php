<?php

namespace tuja\util;

use tuja\Plugin;

class DB {
	
	static public function get_table($name) {
		global $wpdb;
			
		return $wpdb->prefix . Plugin::TABLE_PREFIX . $name;
	}


	static public function add_foreign_key($table, $key, $references, $on_delete = 'RESTRICT') {
		global $wpdb;

		$con_name = $table . '_' . $key . '_' . $references . '_id';   
		$table = self::get_table($table);
		$references = self::get_table($references);

		$check = $wpdb->prepare('SELECT DISTINCT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = %s AND CONSTRAINT_NAME = %s', $wpdb->dbname, $con_name);
		$exists = $wpdb->get_var($check);

		if(!$exists) {
			$query = "ALTER TABLE $table ADD FOREIGN KEY $con_name ($key) REFERENCES $references (id) ON DELETE $on_delete";

			$wpdb->get_var($query);
			$error = $wpdb->last_error;
	
			if($error) {
				throw new \Exception("Unable to create foreign key $table.$key. $error");
			}
		}

		return true;
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