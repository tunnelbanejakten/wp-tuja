<?php
namespace tuja\data\model;


class Upload {
	public $id;
	public $group_id;
	public $hash;
	public $paths;
	public $edits;
	public $is_favourite;
	public $created_at;

	public function validate() {
		// No-op.
	}
}
