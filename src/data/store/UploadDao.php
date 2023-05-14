<?php

namespace tuja\data\store;

use DateTime;
use tuja\data\model\Group;
use tuja\data\model\Upload;
use tuja\data\model\UploadId;
use tuja\util\Database;

class UploadDao extends AbstractDao {

	function __construct() {
		parent::__construct();
		$this->table          = Database::get_table( 'uploads' );
		$this->table_versions = Database::get_table( 'upload_versions' );
	}

	function create( UploadId $upload_id, Group $group ) {
		$query = '
		INSERT IGNORE INTO ' . $this->table . '
			(id, team_id, edits, is_favourite, created_at)
			VALUES
			(%s, %d, %s, %d, %d)
		';

		$affected_rows = $this->wpdb->query(
			$this->wpdb->prepare(
				$query,
				//
				strval( $upload_id ),
				$group->id,
				json_encode( array() ),
				0,
				self::to_db_date( new DateTime() ),
			)
		);

		$success = $affected_rows !== false;

		return $success;
	}

	function create_version( UploadId $upload_id, string $path, string $label ) {
		$query = '
		INSERT IGNORE INTO ' . $this->table_versions . '
			(path, upload_id, label, created_at)
			VALUES
			(%s, %s, %s, %d)
		';

		$affected_rows = $this->wpdb->query(
			$this->wpdb->prepare(
				$query,
				//
				$path,
				strval( $upload_id ),
				$label,
				self::to_db_date( new DateTime() ),
			)
		);

		$success = $affected_rows !== false;

		return $success;
	}

	// function add_path( string $file_hash, string $key, string $path ) {
	// 	$query_template = 'UPDATE ' . $this->table . " SET paths = JSON_SET(paths, '$." . $key . "', %s) WHERE hash = %s";

	// 	return $this->wpdb->query( $this->wpdb->prepare( $query_template, $path, $file_hash ) );

	// }

	// function update( Upload $upload ) {
	// 	$upload->validate();

	// 	return $this->wpdb->update(
	// 		$this->table,
	// 		array(
	// 			'team_id'      => $upload->group_id,
	// 			'paths'        => json_encode( $upload->paths ),
	// 			'edits'        => json_encode( $upload->edits ),
	// 			'is_favourite' => $upload->is_favourite ? 1 : 0,
	// 		),
	// 		array(
	// 			'id' => $upload->id,
	// 		)
	// 	);
	// }

	// function delete( $id ) {
	// 	$query_template = 'DELETE FROM ' . $this->table . ' WHERE id = %d';

	// 	return $this->wpdb->query( $this->wpdb->prepare( $query_template, $id ) );
	// }

	// function get( $id ) {
	// 	return $this->get_object(
	// 		function ( $row ) {
	// 			return self::to_upload( $row );
	// 		},
	// 		'SELECT * FROM ' . $this->table . ' WHERE id = %d',
	// 		$id
	// 	);
	// }

	// function get_by_hash( $key ) {
	// 	return $this->get_object(
	// 		function ( $row ) {
	// 			return self::to_upload( $row );
	// 		},
	// 		'SELECT * FROM ' . $this->table . ' WHERE hash = %s',
	// 		$key
	// 	);
	// }

	// function get_all_in_competition( $competition_id ) {
	// 	return $this->get_objects(
	// 		function ( $row ) {
	// 			return self::to_upload( $row );
	// 		},
	// 		'SELECT * FROM ' . $this->table . ' WHERE competition_id = %d',
	// 		$competition_id
	// 	);
	// }

	// protected static function to_upload( $result ): Upload {
	// 	$upload               = new Upload();
	// 	$upload->id           = intval( $result->id );
	// 	$upload->group_id     = isset( $result->team_id ) ? intval( $result->team_id ) : null;
	// 	$upload->hash         = $result->hash;
	// 	$upload->paths        = json_decode( $result->paths, true );
	// 	$upload->edits        = json_decode( $result->edits, true );
	// 	$upload->is_favourite = '1' === $result->is_favourite;
	// 	$upload->created_at   = self::from_db_date( $result->created_at );

	// 	return $upload;
	// }

}
