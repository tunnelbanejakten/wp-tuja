<?php
namespace tuja\data\model;

use DateTimeInterface;

class Upload {
	public $id;
	public $group_id;
	public $is_favourite;
	public $created_at;
	public $versions;

	public function __construct(
		UploadId $id,
		int $group_id,
		bool $is_favourite,
		DateTimeInterface $created_at = null,
	) {
		$this->id           = $id;
		$this->group_id     = $group_id;
		$this->is_favourite = $is_favourite;
		$this->created_at   = $created_at;
		$this->versions     = array();
	}
}
