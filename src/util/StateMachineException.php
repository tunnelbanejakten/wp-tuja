<?php

namespace tuja\util;


use Exception;

class StateMachineException extends Exception {
	public function __construct( string $message = "" ) {
		parent::__construct( $message, 0, null );
	}
}