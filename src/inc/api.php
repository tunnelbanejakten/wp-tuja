<?php

namespace tuja;

use DateTime;
use tuja\util\JwtUtils;
use Exception;
use WP_Error;

class API extends Plugin {

	const UNAUTHENTICATED_ENDPOINTS = array(
		'/tuja/v1/ping',
		'/tuja/v1/tokens',
		'/tuja/v1/auth',
		'/tuja/v1/update',
		'/tuja/v1',
	);

	const HEADER_TUJA_TIMING_REQUEST_START = 'Tuja-Timing-Request-Start';
	const HEADER_TUJA_TIMING_REQUEST_END   = 'Tuja-Timing-Request-End';

	public function init() {
		add_action( 'rest_api_init', array( $this, 'setup_rest_routes' ) );
		add_filter( 'rest_pre_dispatch', array( $this, 'auth' ), 10, 3 );
		add_filter( 'rest_pre_dispatch', array( $this, 'set_request_started_header' ), 1, 3 );
		add_filter( 'rest_post_dispatch', array( $this, 'set_request_ended_header' ), 10, 3 );

		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		add_filter(
			'rest_pre_serve_request',
			function( $value ) {
				$extra_headers = implode(
					', ',
					array(
						'Accept-Language',
						'Content-Type',
						'User-Agent',
						self::HEADER_TUJA_TIMING_REQUEST_START,
						self::HEADER_TUJA_TIMING_REQUEST_END,
					)
				);
				header( 'Access-Control-Allow-Origin: *' );
				header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT' );
				header( 'Access-Control-Allow-Headers: ' . $extra_headers );
				header( 'Access-Control-Expose-Headers: ' . $extra_headers );
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

	private static function get_current_timestamp_ms() {
		return round( microtime( true ) * 1000 );
	}

	public function set_request_started_header( $res, $server, \WP_REST_Request $request ) {
		$request->set_param( self::HEADER_TUJA_TIMING_REQUEST_START, self::get_current_timestamp_ms() );

		return $res;
	}

	public function set_request_ended_header( $res, $server, \WP_REST_Request $request ) {
		if ( $res instanceof \WP_REST_Response ) {
			$res->header( self::HEADER_TUJA_TIMING_REQUEST_START, $request->get_param( self::HEADER_TUJA_TIMING_REQUEST_START ) );
			$res->header( self::HEADER_TUJA_TIMING_REQUEST_END, self::get_current_timestamp_ms() );
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
			'/configuration/',
			array(
				'methods'             => 'GET',
				'callback'            => $this->callback( 'Configuration', 'get_configuration' ),
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
			'/tickets',
			array(
				'methods'             => 'GET',
				'callback'            => $this->callback( 'Tickets', 'get_tickets' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'tuja/v1',
			'/tickets/request',
			array(
				'methods'             => 'POST',
				'callback'            => $this->callback( 'Tickets', 'redeem_password' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'tuja/v1',
			'/questions',
			array(
				'methods'             => 'GET',
				'callback'            => $this->callback( 'Questions', 'get_all_questions' ),
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

		register_rest_route(
			'tuja/v1',
			'/questions/(?P<id>[a-z0-9]{1,10})/answer',
			array(
				'methods'             => 'POST',
				'callback'            => $this->callback( 'Questions', 'post_answer' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'tuja/v1',
			'/questions/(?P<id>[a-z0-9]{1,10})/view-events',
			array(
				'methods'             => 'POST',
				'callback'            => $this->callback( 'Questions', 'post_view_event' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'tuja/v1',
			'/duels',
			array(
				'methods'             => 'GET',
				'callback'            => $this->callback( 'Duels', 'get_duels' ),
				'permission_callback' => '__return_true',
			)
		);

	}

	public function callback( $controller, $method ) {
		return array( __NAMESPACE__ . '\\' . $controller, $method );
	}
}

new API();
