<?php

namespace tuja;

use tuja\data\model\Ticket;
use tuja\data\model\Group;
use tuja\data\store\GroupDao;
use tuja\data\store\TicketDao;
use WP_REST_Request;
use Exception;
use tuja\util\ticket\CouponToTicket;
use tuja\util\ticket\CouponToTicketTsl2020;

class AvailableFormObjects {
	public $form_ids           = array();
	public $question_group_ids = array();
	public $question_ids       = array();
}

class Tickets extends AbstractRestEndpoint {


	public static function get_tickets( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( false === $group ) {
			return self::create_response( 404 ); // Not Found.
		}

		return self::get_tickets_response( $group );
	}

	private static function get_tickets_response( Group $group ) {
		$ticket_dao = new TicketDao();
		$tickets    = $ticket_dao->get_group_tickets( $group );

		return array_values(
			array_map(
				function ( Ticket $ticket ) {
					return array(
						'colour'  => $ticket->colour,
						'word'    => $ticket->word,
						'symbol'  => $ticket->symbol,
						'is_used' => $ticket->is_used,
						'station' => array(
							'name'      => $ticket->station->name,
							'random_id' => $ticket->station->random_id,
						),
					);
				},
				$tickets,
			)
		);
	}

	public static function redeem_password( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( false === $group ) {
			return self::create_response( 404 ); // Not Found.
		}

		try {
			$ticket_validator = new CouponToTicketTsl2020();
			$body             = $request->get_json_params();
			$password         = $body['password'];
			if ( empty( $password ) ) {
				return self::create_response( 400 ); // Bad Request
			}
			$new_stations = $ticket_validator->get_tickets_from_coupon_code( $group, $password );

			return array(
				'added_tickets' => count( $new_stations ),
				'all_tickets'   => self::get_tickets_response( $group ),
			);
		} catch ( Exception $e ) {
			switch ( $e->getCode() ) {
				case CouponToTicket::ERROR_CODE_COUPON_ALREADY_USED:
					return self::create_response( 409 );
				case CouponToTicket::ERROR_CODE_INVALID_COUPON:
					return self::create_response( 404 );
				default:
					return self::create_response( 500 );
			}
		}
	}
}
