<?php

namespace tuja;

class Ping extends AbstractRestEndpoint {

	public function get_ping() {
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Access-Control-Expose-Headers: Link', false );

		return ['status' => 'ok'];
	}
}