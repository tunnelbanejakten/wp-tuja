<?php

namespace tuja\data\store;

use DateTime;
use tuja\data\model\Upload;
use tuja\util\Database;

class UploadDao extends AbstractDao {

	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'uploads' );
	}

	function create( Upload $upload ) {
		$upload->validate();

		$query = '
		INSERT IGNORE INTO ' . $this->table . '
			(team_id, hash, paths, edits, is_favourite, created_at)
			VALUES
			(%d, %s, %s, %s, %d, %d)
		';

		$affected_rows = $this->wpdb->query(
			$this->wpdb->prepare(
				$query,
				//
				$upload->group_id,
				$upload->hash,
				json_encode( $upload->paths ),
				json_encode( $upload->edits ),
				$upload->is_favourite ? 1 : 0,
				self::to_db_date( new DateTime() ),
			)
		);

		$success = $affected_rows !== false;

		return $success;

	}

	function add_path( string $file_hash, string $key, string $path ) {
		$query_template = 'UPDATE ' . $this->table . " SET paths = JSON_SET(paths, '$." . $key . "', %s) WHERE hash = %s";

		return $this->wpdb->query( $this->wpdb->prepare( $query_template, $path, $file_hash ) );

	}

		$upload               = new Upload();
		$upload->id           = intval( $result->id );
		$upload->group_id     = isset( $result->team_id ) ? intval( $result->team_id ) : null;
		$upload->hash         = $result->hash;
		$upload->paths        = json_decode( $result->paths, true );
		$upload->edits        = json_decode( $result->edits, true );
		$upload->is_favourite = '1' === $result->is_favourite;
		$upload->created_at   = self::from_db_date( $result->created_at );

		return $upload;
	}

}
