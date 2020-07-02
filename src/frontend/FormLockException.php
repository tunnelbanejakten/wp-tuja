<?php


namespace tuja\frontend;


class FormLockException extends \Exception {

	private $rejected_ids;

	public function __construct( string $message, array $rejected_ids ) {
		parent::__construct( $message );
		$this->rejected_ids = $rejected_ids;
	}

	public function get_rejected_ids(): array {
		return $this->rejected_ids;
	}
}