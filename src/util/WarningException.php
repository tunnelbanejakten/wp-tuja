<?php


namespace tuja\util;


use Exception;
use Throwable;

class WarningException extends Exception {
	public function __construct( $message = "", $code = 0, Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}
}