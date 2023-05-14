<?php
namespace tuja\data\model;


class UploadId {
	public $group_key;
	public $file_hash;

	public function __construct( string $group_key = null, string $file_hash = null ) {
		$this->group_key = $group_key ?? 'unspecified';
		$this->file_hash = $file_hash ?? 'unspecified';
	}

	public function __toString() {
		return $this->group_key . '/' . $this->file_hash;
	}

	public static function from_string( string $upload_id ) {
		list ($group_key, $file_hash) = explode( '/', $upload_id, 2 );
		return new UploadId( 'unspecified' === $group_key ? null : $group_key, $file_hash );
	}
}
