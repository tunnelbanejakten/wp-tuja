<?php
namespace tuja\data\model;

use DateTime;

class Upload {
	public $id;
	public $group_id;
	public $edits;
	public $is_favourite;
	public $created_at;

	public function __construct(
		UploadId $id,
		int $group_id,
		array $edits,
		bool $is_favourite,
		DateTime $created_at,
	) {
		$this->id           = $id;
		$this->group_id     = $group_id;
		$this->edits        = $edits;
		$this->is_favourite = $is_favourite;
		$this->created_at   = $created_at;
	}
}
