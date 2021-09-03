<?php

namespace tuja;

use tuja\data\store\GroupDao;
use tuja\data\store\MapDao;
use tuja\data\store\MarkerDao;
use tuja\data\store\ResponseDao;
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

		$response_dao = new ResponseDao();
		$responses    = $response_dao->get_latest_by_group( $group_id );

		return array_map(
			function ( Marker $marker ) use ( $responses ) {
				return array(
					'latitude'               => $marker->gps_coord_lat,
					'longitude'              => $marker->gps_coord_long,
					'radius'                 => 25,
					'name'                   => $marker->name,
					'link_form_id'           => isset( $marker->link_form_id ) ? intval( $marker->link_form_id ) : null,
					'link_form_question_id'  => isset( $marker->link_form_question_id ) ? intval( $marker->link_form_question_id ) : null,
					'link_question_group_id' => isset( $marker->link_question_group_id ) ? intval( $marker->link_question_group_id ) : null,
					'link_station_id'        => isset( $marker->link_station_id ) ? intval( $marker->link_station_id ) : null,
					'is_response_submitted'  => isset( $responses[ $marker->link_form_question_id ] ),
				);
			},
			$markers
		);
	}
}
