<?php

namespace tuja;

class Ping extends AbstractRestEndpoint {

	public static function get_ping() {
		return array( 'status' => 'ok' );
	}
}
