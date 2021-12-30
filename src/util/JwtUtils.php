<?php

namespace tuja\util;

use Firebase\JWT\JWT;


class JwtUtils {
	const ALGORITHM = 'HS256';

	private static function jwt_secret() {
		$jwt_secret = defined( 'JWT_SECRET' )
			? JWT_SECRET // Use constant from wp-settings.php by default
			: ( isset( $_ENV['JWT_SECRET'] )
				? $_ENV['JWT_SECRET'] // Use environment variable if constant not defined
				: null );

		if ( ! isset( $jwt_secret ) ) {
			return new WP_Error( 'missing_jwt_secret', 'JWT secret is missing.', array( 'status' => 500 ) );
		}

		return $jwt_secret;
	}

	public static function create_token( int $competition_id, int $group_id, string $group_key ) {
		$jwt_secret = self::jwt_secret();
		$payload    = array(
			'group_id'       => $group_id,
			'group_key'      => $group_key,
			'competition_id' => $competition_id,
		);
		return JWT::encode( $payload, $jwt_secret, self::ALGORITHM );
	}

	public static function decode( $token ) {
		$jwt_secret = self::jwt_secret();
		return JWT::decode( $token, $jwt_secret, array( self::ALGORITHM ) );
	}
}
