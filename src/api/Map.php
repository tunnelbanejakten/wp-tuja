<?php

namespace tuja;

use tuja\data\store\GroupDao;
use tuja\data\store\MapDao;
use tuja\data\store\MarkerDao;
use tuja\data\model\Marker;
use tuja\util\JwtUtils;
use WP_REST_Request;
use WP_REST_Response;

class Map extends AbstractRestEndpoint {

	public static function get_markers( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( $group === false ) {
			return self::create_response( 404 );
		}

		$marker_dao = new MarkerDao();
		$markers    = $marker_dao->get_all_on_map( $group->map_id );

		return array_map(
			function ( Marker $marker ) {
				return array(
					'latitude'  => $marker->gps_coord_lat,
					'longitude' => $marker->gps_coord_long,
					'radius'    => 25,
					'name'      => $marker->name,
				);
			},
			$markers
		);
	}
}
