<?php

namespace tuja;

use tuja\util\JwtUtils;
use Exception;
use GuzzleHttp\Psr7\Request;
use WP_Error;

class API extends Plugin {

	const UNAUTHENTICATED_ENDPOINTS = array(
		'/tuja/v1/ping',
		'/tuja/v1/tokens',
		'/tuja/v1/auth',
		'/tuja/v1/update',
		'/tuja/v1',
	);

	public function init() {
		add_action( 'rest_api_init', array( $this, 'setup_rest_routes' ) );
		add_filter( 'rest_pre_dispatch', array( $this, 'auth' ), 10, 3 );

		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		add_filter(
			'rest_pre_serve_request',
			function( $value ) {
				header( 'Access-Control-Allow-Origin: *' );
				header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT' );
				header( 'Access-Control-Allow-Headers: Accept-Language, Content-Type, User-Agent' );
				header( 'Access-Control-Allow-Credentials: true' );
				header( 'Access-Control-Expose-Headers: Link', false );

				return $value;
			}
		);
	}

	public function auth( $res, $server, \WP_REST_Request $request ) {
		if ( in_array( $request->get_route(), self::UNAUTHENTICATED_ENDPOINTS, true ) || strpos( $request->get_route(), '/tuja/v1' ) !== 0 ) {
			return $res;
		}

		$token = $request->get_param( 'token' );
		if ( is_null( $token ) ) {
			return new WP_Error( 'not_authenticated', 'You have not provided credentials.', array( 'status' => 401 ) );
		}

		try {
			$decoded = JwtUtils::decode( $token );

			$request->set_param( 'token_decoded', $decoded );
		} catch ( Exception $e ) {
			return new WP_Error( 'not_authenticated', 'The provided credentials are invalid.', array( 'status' => 401 ) );
		}

		return $res;
	}

	public function setup_rest_routes() {
		register_rest_route(
			'tuja/v1',
			'/ping/',
			array(
				'methods'             => 'GET',
				'callback'            => $this->callback( 'Ping', 'get_ping' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'tuja/v1',
			'/tokens/',
			array(
				'methods'             => 'POST',
				'callback'            => $this->callback( 'Auth', 'post_tokens' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'tuja/v1',
			'/profile/',
			array(
				'methods'             => 'GET',
				'callback'            => $this->callback( 'Profile', 'get_profile' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'tuja/v1',
			'/map/markers',
			array(
				'methods'             => 'GET',
				'callback'            => $this->callback( 'Map', 'get_markers' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'tuja/v1',
			'/questions/(?P<id>[a-z0-9]{1,10})',
			array(
				'methods'             => 'GET',
				'callback'            => $this->callback( 'Questions', 'get_question' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function callback( $controller, $method ) {
		return array( __NAMESPACE__ . '\\' . $controller, $method );
	}
}

new API();
