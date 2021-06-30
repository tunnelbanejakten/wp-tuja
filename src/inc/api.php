<?php

namespace tuja;

use tuja\util\JwtUtils;
use Exception;
use WP_Error;

class API extends Plugin {

	public function init() {
		add_action('rest_api_init', [$this, 'setup_rest_routes']);
		add_filter('rest_pre_dispatch', [$this, 'auth'], 10, 3);

		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		add_filter( 'rest_pre_serve_request', function( $value ) {
			header( 'Access-Control-Allow-Origin: *' );
			header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT' );
			header( 'Access-Control-Allow-Credentials: true' );
			header( 'Access-Control-Expose-Headers: Link', false );

			return $value;
		} );
	}

	public function auth($res, $server, $request) {
		if($request->get_route() === '/tuja/v1/auth' || $request->get_route() === '/tuja/v1/update' || $request->get_route() === '/tuja/v1' || strpos($request->get_route(), '/tuja/v1') !== 0) {
			return $res;
		}

		$token = $request->get_param('token');
		if(is_null($token)) {
			return new WP_Error('not_authorized', 'You are not authorized to access this route.', ['status' => 401]);
		}

		try {
			$decoded = JwtUtils::decode($token);

			// TODO - validate that the group ID exists
			// if(!$decoded->group_id) {
			// 	throw new Exception();
			// }
		} catch (Exception $e) {
			return new WP_Error('not_authorized', 'You are not authorized to access this route.', ['status' => 401]);
		}
		
		return $res;
	}

	public function setup_rest_routes() {
		register_rest_route('tuja/v1', '/ping/', [
			'method' => 'GET',
			'callback' => $this->callback('Ping', 'get_ping'),
			'permission_callback' => '__return_true'
		]);
	}

	public function callback($controller, $method) {
		return [__NAMESPACE__ . '\\' . $controller, $method];
	}
}

new API();
