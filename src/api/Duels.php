<?php

namespace tuja;

use tuja\data\store\DuelDao;
use tuja\data\store\GroupDao;
use WP_REST_Request;

class Duels extends AbstractRestEndpoint {

	public static function get_duels( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( $group === false ) {
			return self::create_response( 404 );
		}

		$duel_dao        = new DuelDao();
		$group_duel_data = $duel_dao->get_duels_by_group( $group );

		return $group_duel_data;
	}
}
