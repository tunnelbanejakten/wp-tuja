<?php

namespace tuja;

use tuja\data\store\GroupDao;
use tuja\data\model\Group;
use tuja\util\JwtUtils;
use WP_REST_Request;
use WP_REST_Response;

class Auth extends AbstractRestEndpoint {

	public static function post_tokens( WP_REST_Request $request ) {
		$payload      = $request->get_json_params();
		$group_dao    = new GroupDao();

		// Start by checking for numeric sign-in code
		$payload_code = @$payload['code'];
		if (!empty ($payload_code )) {
			if (! is_string( $payload_code )) {
				return self::create_response( 400 );
			}
			
			$code_length = strlen($payload_code);
			if ($code_length < GroupDao::AUTH_CODE_MIN_LENGTH || $code_length > GroupDao::AUTH_CODE_MAX_LENGTH) {
				return self::create_response( 400 );
			}
			
			$group     = $group_dao->get_by_auth_code( $payload_code );
			if ( false === $group ) {
				return self::create_response( 401 );
			}
			
			return self::create_token_response( $group );
		}
		
		// Continue by checking for alpha-numeric sign-in key
		$payload_id  = @$payload['id'];
		if ( !empty( $payload_id ) ) {
			if (! is_string( $payload_id )) {
				return self::create_response( 400 );
			}
			
			$group     = $group_dao->get_by_key( $payload_id );
			if ( false === $group ) {
				return self::create_response( 401 );
			}

			return self::create_token_response( $group );
		}
		
		return self::create_response( 400 );
	}

	private static function create_token_response ( Group $group ) : array {
		return array(
			'token' => JwtUtils::create_token( $group->competition_id, $group->id, $group->random_id ),
		);
	}
}
