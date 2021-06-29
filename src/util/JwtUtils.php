<?php

namespace tuja\util;

use Firebase\JWT\JWT;


class JwtUtils {
	private static function jwt_secret() {
		$jwt_secret = defined('JWT_SECRET')
			? JWT_SECRET // Use constant from wp-settings.php by default
			: (isset($_ENV['JWT_SECRET'])
				? $_ENV['JWT_SECRET'] // Use environment variable if constant not defined
				: null);

		if (!isset($jwt_secret)) {
			return new WP_Error('missing_jwt_secret', 'JWT secret is missing.', ['status' => 500]);
		}

		return $jwt_secret;
	}

	public static function create_token($group_id) {
		$jwt_secret = self::jwt_secret();
		$payload = array(
			"sub" => $group_id
		);
		return JWT::encode($payload, $jwt_secret, 'HS256');
	}

	public static function decode($token) {
		$jwt_secret = self::jwt_secret();
		return JWT::decode($token, $jwt_secret, ['HS256']);
	}
}
