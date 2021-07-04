<?php

namespace tuja;

use WP_REST_Response;

abstract class AbstractRestEndpoint {
	protected static function create_response( int $status_code ) {
		$response = new WP_REST_Response();
		$response->set_status( $status_code );
		return $response;
	}
}
