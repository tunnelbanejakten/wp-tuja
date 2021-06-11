<?php

namespace tuja;

use Exception;
use Firebase\JWT\JWT;
use WP_Error;

class API extends Plugin {

	public function init() {
		add_action('rest_api_init', [$this, 'setup_rest_routes']);
		add_filter('rest_pre_dispatch', [$this, 'auth'], 10, 3);
	}


	public function auth($res, $server, $request) {
		if($request->get_route() === '/tuja/v1/auth' || strpos($request->get_route(), '/tuja/v1') !== 0) {
			return $res;
		}

		if(!defined('JWT_SECRET')) {
			return new WP_Error('missing_jwt_secret', 'JWT secret is missing.', ['status' => 500]);
		}

		$token = $request->get_param('token');
		if(is_null($token)) {
			return new WP_Error('not_authorized', 'You are not authorized to access this route.', ['status' => 401]);
		}

		try {
			$decoded = JWT::decode($token, JWT_SECRET, ['HS256']);

			// TODO - validate that the group ID exists
			if(!$decoded->group_id) {
				throw new Exception();
			}
		} catch(Exception $e) {
			return new WP_Error('not_authorized', 'You are not authorized to access this route.', ['status' => 401]);
		}
		
		return $res;
	}


	public function setup_rest_routes() {
		register_rest_route('tuja/v1', '/ping/', [
			'method' => 'GET',
			'callback' => $this->callback('Ping', 'get_ping')
		]);
	}


	public function callback($controller, $method) {
		return [__NAMESPACE__ . '\\' . $controller, $method];
	}

}

new API();