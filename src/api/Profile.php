<?php

namespace tuja;

use tuja\data\store\GroupDao;
use tuja\util\JwtUtils;
use WP_REST_Request;
use WP_REST_Response;

class Profile extends AbstractRestEndpoint {

	public static function get_profile( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( $group === false ) {
			return self::create_response( 404 );
		}

		return array(
			'id'                 => $group->id,
			'key'                => $group->random_id,
			'name'               => $group->name,

			'count_competing'    => $group->count_competing,
			'count_follower'     => $group->count_follower,
			'count_team_contact' => $group->count_team_contact,
		);
	}
}
