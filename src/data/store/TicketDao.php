<?php


namespace tuja\data\store;


use tuja\data\model\Competition;
use tuja\data\model\Group;
use tuja\data\model\Station;
use tuja\data\model\StationWeight;
use tuja\data\model\Ticket;
use tuja\data\model\TicketDesign;
use tuja\util\Database;

class TicketDao extends AbstractDao {

	public function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'ticket' );
	}

	public function get_station( int $competition_id, string $on_complete_password ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_station( $row );
			},
			'SELECT s.* FROM ' . Database::get_table( 'ticket_station_config' ) . ' AS tsc INNER JOIN ' . Database::get_table( 'station' ) . ' AS s ON tsc.station_id = s.id WHERE tsc.on_complete_password = %s AND s.competition_id = %d',
			self::normalize_string($on_complete_password),
			$competition_id );
	}

	public function get_station_weights( int $competition_id ) {
		$query      = '
			SELECT 
	        	from_station_id,
				to_station_id,
				to_weight 
			FROM ' . Database::get_table( 'ticket_coupon_weight' ) . ' AS tcw 
				INNER JOIN ' . Database::get_table( 'station' ) . ' AS s 
				ON tcw.from_station_id = s.id 
			WHERE 
				s.competition_id = %d';
		$db_results = $this->wpdb->get_results( $this->wpdb->prepare( $query, [
			$competition_id
		] ), OBJECT );
//		var_dump($competition_id);
//		var_dump($db_results);
		$results = [];
		foreach ( $db_results as $result ) {
			$results[] = new StationWeight(
				$result->from_station_id,
				$result->to_station_id,
				floatval( $result->to_weight ) );
		}

		return $results;
	}

	public function set_station_weights( Competition $competition, array $station_weights ) {
		$reset_query = '
			DELETE 
				FROM ' . Database::get_table( 'ticket_coupon_weight' ) . ' 
			WHERE 
				from_station_id IN (
					SELECT id 
					FROM ' . Database::get_table( 'station' ) . ' 
					WHERE competition_id = %d)';
		$this->wpdb->query( $this->wpdb->prepare( $reset_query, [ $competition->id ] ) );

		array_walk( $station_weights, function ( StationWeight $station_weight ) {
			$reset_query = '
				INSERT INTO ' . Database::get_table( 'ticket_coupon_weight' ) . ' 
				(from_station_id, to_station_id, to_weight)
				VALUES 
				(%s, %d, %d)';
			$this->wpdb->query( $this->wpdb->prepare( $reset_query,
				[ $station_weight->from_station_id, $station_weight->to_station_id, $station_weight->weight ]
			) );
		} );

		foreach ( $station_weights as $station_id => $weight ) {
		}
	}

	public function get_group_tickets( Group $group ) {
		$query      = '
			SELECT 
	        	tsd.*, s.*, t.*
			FROM ' . Database::get_table( 'ticket' ) . ' AS t
				INNER JOIN ' . Database::get_table( 'station' ) . ' AS s 
				ON t.station_id = s.id 
				INNER JOIN ' . Database::get_table( 'ticket_station_config' ) . ' AS tsd 
				ON t.station_id = tsd.station_id 
			WHERE 
				t.team_id = %d';
		$db_results = $this->wpdb->get_results( $this->wpdb->prepare( $query, [
			$group->id
		] ), OBJECT );

		return array_map( function ( $result ) {
			$ticket                            = new Ticket();
			$ticket->on_complete_password_used = $result->on_complete_password_used;
			$ticket->colour                    = $result->colour;
			$ticket->word                      = $result->word;
			$ticket->symbol                    = $result->symbol;
			$ticket->station                   = self::to_station( $result ); // Relies on fact that no column names are shared between tables ticket_station_config and station.

			return $ticket;
		}, $db_results );
	}

	public function get_ticket_designs( Competition $competition ) {
		$query      = '
			SELECT 
	        	tsc.* 
			FROM ' . Database::get_table( 'ticket_station_config' ) . ' AS tsc 
				INNER JOIN ' . Database::get_table( 'station' ) . ' AS s 
				ON s.id = tsc.station_id 
			WHERE 
				s.competition_id = %d';
		$db_results = $this->wpdb->get_results( $this->wpdb->prepare( $query, [
			$competition->id
		] ), OBJECT );

		$designs = array_map( function ( $result ) {
			return self::to_ticket_design( $result );
		}, $db_results );

		return array_combine( array_map( function ( TicketDesign $ticket_design ) {
			return $ticket_design->station_id;
		}, $designs ), $designs );
	}

	public function set_ticket_designs( array $ticket_designs ) {
		array_walk( $ticket_designs, function ( TicketDesign $ticket_design ) {
			$reset_query = 'DELETE FROM ' . Database::get_table( 'ticket_station_config' ) . ' WHERE station_id = %d';
			$this->wpdb->query( $this->wpdb->prepare( $reset_query, [ $ticket_design->station_id ] ) );

			$reset_query = '
				INSERT INTO ' . Database::get_table( 'ticket_station_config' ) . ' 
				(station_id, colour, word, symbol, on_complete_password)
				VALUES 
				(%d, %s, %s, %s, %s)';
			$this->wpdb->query( $this->wpdb->prepare( $reset_query,
				[
					$ticket_design->station_id,
					$ticket_design->colour,
					$ticket_design->word,
					$ticket_design->symbol,
					self::normalize_string($ticket_design->on_complete_password)
				]
			) );
		} );
	}

	public function grant_ticket( int $group_id, int $station_id, string $on_complete_password_used ) {
		$reset_query   = '
				INSERT INTO ' . Database::get_table( 'ticket' ) . ' 
				(team_id, station_id, on_complete_password_used)
				VALUES 
				(%d, %d, %s)';
		$affected_rows = $this->wpdb->query( $this->wpdb->prepare( $reset_query, [
			$group_id,
			$station_id,
			self::normalize_string( $on_complete_password_used )
		] ) );

		return $affected_rows === 1;
	}

	public static function normalize_string($str) {
		if ( $str == null ) {
			return null;
		}
		return trim( strtolower( $str ) );
	}
}