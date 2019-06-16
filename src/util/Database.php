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

	public static function fix_questions_not_in_group() {
		global $wpdb;
		$id = new Id();

		$query = 'SELECT id, form_id, sort_order FROM ' . self::get_table( 'form_question' ) . ' WHERE question_group_id IS NULL';

		$db_results = $wpdb->get_results( $wpdb->prepare( $query, [] ), OBJECT );
		foreach ( $db_results as $result ) {

			$affected_rows = $wpdb->insert( self::get_table( 'form_question_group' ),
				array(
					'random_id'  => $id->random_string(),
					'form_id'    => $result->form_id,
					'sort_order' => $result->sort_order
				),
				array(
					'%s',
					'%d',
					'%d'
				) );

			$question_group_id = $wpdb->insert_id;
			if ( $affected_rows === false || $affected_rows !== 1 || ! is_numeric( $question_group_id ) ) {
				throw new Exception( 'Could not create question group for question ' . $result->id );
			}

			$affected_rows = $wpdb->update( self::get_table( 'form_question' ),
				array(
					'random_id'         => $id->random_string(),
					'question_group_id' => $question_group_id,
					'form_id'           => null
				),
				array(
					'id' => $result->id
				) );

			if ( $affected_rows === false || $affected_rows !== 1 ) {
				throw new Exception( 'Could not update question ' . $result->id );
			}
		}
	}

	public static function fix_teams_history() {
		global $wpdb;
		$teams_edits_count = intval( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::get_table( 'team_properties' ) ) );
		$teams_count       = intval( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::get_table( 'team' ) ) );
		if ( $teams_edits_count == 0 && $teams_count > 0 ) {
			$copy_fields = [
				'name',
				'category_id'
			];

			// copy data from source table to "history table"
			$query = '
				INSERT INTO ' . self::get_table( 'team_properties' ) . ' (
					team_id,
					created_at,
                    status,
					' . join( ',', $copy_fields ) . '
				) SELECT 
					id,
					' . ( new DateTime() )->getTimestamp() . ',
					"' . Group::STATUS_CREATED . '",
					' . join( ',', $copy_fields ) . '
				  FROM ' . self::get_table( 'team' );

			if ( $wpdb->query( $query ) === false ) {
				throw new Exception( 'Could not copy data from team source table to history table.' );
			};

			foreach (
				[
					[
						"ignore_error" => true,
						"query"        => 'alter table ' . self::get_table( 'team' ) . ' drop foreign key team_competition_id_competition_id'
					],
					[
						"ignore_error" => true,
						"query"        => 'alter table ' . self::get_table( 'team' ) . ' drop key idx_team_name'
					],
					[
						"ignore_error" => false,
						"query"        => 'alter table ' . self::get_table( 'team' ) . ' add constraint team_competition_id_competition_id FOREIGN KEY (competition_id) REFERENCES wp_tuja_competition (id) ON DELETE CASCADE'
					],
					[
						"ignore_error" => true,
						"query"        => 'alter table ' . self::get_table( 'team' ) . ' drop foreign key team_category_id_team_category_id'
					],
					[
						"ignore_error" => false,
						"query"        => 'alter table ' . self::get_table( 'team' ) . ' drop key team_category_id_team_category_id'
					]
				] as $args
			) {
				$res = $wpdb->query( $args['query'] );
				if ( $res === false && ! $args['ignore_error'] ) {
					throw new Exception( 'Could not drop key from team source table.' );
				};
			}
			// drop old columns from source table
			foreach ( $copy_fields as $column ) {
				$query = 'ALTER TABLE ' . self::get_table( 'team' ) . ' DROP COLUMN ' . $column;
				if ( $wpdb->query( $query ) === false ) {
					throw new Exception( 'Could not drop old columns from team source table.' );
				};
			}
		}
	}

	public static function fix_people_history() {
		global $wpdb;
		$people_edits_count = intval( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::get_table( 'person_properties' ) ) );
		$people_count       = intval( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::get_table( 'person' ) ) );
		if ( $people_edits_count == 0 && $people_count > 0 ) {
			$copy_fields = [
				'name',
				'team_id',
				'phone',
				'phone_verified',
				'email',
				'email_verified',
				'pno',
				'food',
				'is_competing',
				'is_team_contact'
			];

			// copy data from source table to "history table"
			$query = '
				INSERT INTO ' . self::get_table( 'person_properties' ) . ' (
					person_id,
					created_at,
                    status,
					' . join( ',', $copy_fields ) . '
				) SELECT
					id,
					' . ( new DateTime() )->getTimestamp() . ',
					"' . Person::STATUS_CREATED . '",
					' . join( ',', $copy_fields ) . '
				 FROM ' . self::get_table( 'person' );

			if ( $wpdb->query( $query ) === false ) {
				throw new Exception( 'Could not copy data from people source table to history table.' );
			};

			$query = 'ALTER TABLE ' . self::get_table( 'person' ) . ' DROP FOREIGN KEY person_team_id_team_id';
			if ( $wpdb->query( $query ) === false ) {
//				throw new Exception( 'Could not drop person_team_id_team_id from person source table.' );
			};

//			foreach (
//				[
//					'alter table ' . self::get_table( 'person' ) . ' drop foreign key person_team_id_team_id',
//					'alter table ' . self::get_table( 'person' ) . ' drop key person_team_id_team_id'
//				] as $query
//			) {
//				if ( $wpdb->query( $query ) === false ) {
//					throw new Exception( 'Could not drop idx_team_name from team source table.' );
//				};
//			}
			// drop old columns from source table
			foreach ( $copy_fields as $column ) {
				$query = 'ALTER TABLE ' . self::get_table( 'person' ) . ' DROP COLUMN ' . $column;
				if ( $wpdb->query( $query ) === false ) {
					throw new Exception( 'Could not drop old columns from people source table.' );
				};

			}
		}
	}
}