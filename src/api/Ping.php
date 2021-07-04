<?php

namespace tuja;

class Ping extends AbstractRestEndpoint {

	public function get_ping() {
		return array( 'status' => 'ok' );
	}
}
