<?php

namespace tuja;

use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\util\AppUtils;
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
		$category_dao = new GroupCategoryDao();
		$category     = $category_dao->get( $group->category_id );
		if ( $category === false ) {
			return self::create_response( 404 );
		}

		return array(
			'id'                 => $group->id,
			'key'                => $group->random_id,
			'name'               => $group->name,
			'category_name'      => $category->name,

			'portal_link'        => GroupHomeInitiator::link( $group ),
			'app_link'           => AppUtils::group_link( $group ),
			'app_base_link'      => AppUtils::base_link(),

			'count_competing'    => $group->count_competing,
			'count_follower'     => $group->count_follower,
			'count_team_contact' => $group->count_team_contact,

			'auth_code'          => $group->auth_code,
		);
	}
}
