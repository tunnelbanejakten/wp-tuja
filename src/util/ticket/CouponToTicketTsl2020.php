<?php


namespace tuja\util\ticket;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\StationWeight;
use tuja\data\model\Ticket;
use tuja\data\store\StationDao;
use tuja\data\store\TicketDao;

class CouponToTicketTsl2020 implements CouponToTicket {
	private $ticket_dao;
	private $station_dao;

	/**
	 * CouponToTicketTsl2020 constructor.
	 */
	public function __construct() {
		$this->station_dao = new StationDao();
		$this->ticket_dao  = new TicketDao();
	}

	/**
	 * Algorithm:
	 *
	 * The coupon code is associated with a location (station) and shows where in the city a team is (since the team got
	 * the coupon code by completing a task at said station).
	 *
	 * Each location has a list of tuples, each one specifying the distance to another location.
	 *
	 * A subset of locations is used to pick the next locations: The subset possibleNextLocations is the list of
	 * locations (stations) which the team has not yet gotten tickets to.
	 *
	 * The distances from the current location, i.e. the couponLocation, to each of the locations in
	 * possibleNextLocations are calculated. The possibleNextLocations list is sorted based on distance from current location.
	 *
	 * The two nextLocations is one random location from the first half of possibleNextLocations and one random one
	 * from the last half.
	 *
	 * @param Group $group
	 * @param string $coupon_code
	 *
	 * @return array
	 */
	function get_tickets_from_coupon_code( Group $group, string $coupon_code ): array {
		$coupon_code = TicketDao::normalize_string( $coupon_code );
		$station = $this->ticket_dao->get_station( $group->competition_id, $coupon_code );
		if ( $station === false ) {
			throw new Exception( sprintf( 'The password %s is not correct', $coupon_code ), CouponToTicket::ERROR_CODE_INVALID_COUPON );
		}
		$all_station_weights = $this->ticket_dao->get_station_weights( $group->competition_id );
		$station_weights     = array_filter( $all_station_weights, function ( StationWeight $station_weight ) use ( $station ) {
			return $station_weight->from_station_id == $station->id;
		} );
		$team_tickets        = $this->ticket_dao->get_group_tickets( $group );

		$is_ticket_missing = empty(
			array_filter(
				$team_tickets,
				function( Ticket $ticket ) use ( $station ) {
					return $ticket->station->id === $station->id;
				}
			)
		);
		if ( $is_ticket_missing ) {
			// Coupon code belongs to a station for which the team does not have a ticket.
			// This is either a sign that the team has cheated or that they have been allowed
			// to compete at the station without having a ticket. We will assume good intention
			// and retroactively grant the missing ticket so that the team is not "accidentally"
			// granted a ticket to this station later in the competition.
			$this->ticket_dao->grant_ticket( $group->id, $station->id, $coupon_code );
			$team_tickets = $this->ticket_dao->get_group_tickets( $group );
		}

		foreach ( $team_tickets as $team_ticket ) {
			if ( TicketDao::normalize_string( $team_ticket->on_complete_password_used ) == $coupon_code ) {
				throw new Exception( sprintf( 'Cannot use %s twice', $coupon_code ), CouponToTicket::ERROR_CODE_COUPON_ALREADY_USED );
			}
		}

		$team_ticket_station_ids = array_map( function ( Ticket $ticket ) {
			return $ticket->station->id;
		}, $team_tickets );
		$possible_next_stations  = array_filter( $station_weights, function ( StationWeight $station_weight ) use ( $team_ticket_station_ids ) {
			return ! in_array( $station_weight->to_station_id, $team_ticket_station_ids );
		} );

		usort( $possible_next_stations, function ( StationWeight $station_weight_a, StationWeight $station_weight_b ) {
			return $station_weight_a->weight - $station_weight_b->weight;
		} );

		$next_station_ids = [];

		if ( count( $possible_next_stations ) == 1 ) {
			$next_station_ids = [ $possible_next_stations[0]->to_station_id ];
		} elseif ( count( $possible_next_stations ) > 1 ) {
			$pos           = (int) ( count( $possible_next_stations ) / 2 );
			$first_options = array_slice( $possible_next_stations, 0, $pos );
			$other_options = array_slice( $possible_next_stations, $pos );

			$next_station_ids = [
				$first_options[ rand( 0, count( $first_options ) - 1 ) ]->to_station_id,
				$other_options[ rand( 0, count( $other_options ) - 1 ) ]->to_station_id
			];
		}

		if ( count( $next_station_ids ) > 0 ) {
			foreach ( $next_station_ids as $next_station_id ) {
				if ( ! $this->ticket_dao->grant_ticket( $group->id, $next_station_id, $coupon_code ) ) {
					throw new Exception( sprintf( 'Failed to grant ticket to station %d for team %d', $next_station_id, $group->random_id ), CouponToTicket::ERROR_CODE_GENERIC );
				}
			}
		}

		return $next_station_ids;
	}

	function list_tickets( Group $group ): array {
		return $this->ticket_dao->get_group_tickets( $group );
	}
}