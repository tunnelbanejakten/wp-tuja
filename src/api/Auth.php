<?php

namespace tuja;

use tuja\data\store\GroupDao;
use tuja\util\JwtUtils;
use WP_REST_Request;
use WP_REST_Response;

class Auth extends AbstractRestEndpoint {

	public static function post_tokens( WP_REST_Request $request ) {
		$payload    = $request->get_json_params();
		$payload_id = @$payload['id'];
		if ( empty( $payload_id ) || ! is_string( $payload_id ) ) {
			return self::create_response( 400 );
		}

		$group_dao = new GroupDao();
		$group     = $group_dao->get_by_key( $payload_id );
		if ( $group === false ) {
			return self::create_response( 404 );
		}

		return array(
			'token' => JwtUtils::create_token( $group->competition_id, $group->id ),
		);
	}
}
