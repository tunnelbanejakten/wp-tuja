<?php

namespace tuja;

use tuja\data\store\GroupDao;
use tuja\data\store\MarkerDao;
use tuja\data\store\ResponseDao;
use tuja\data\model\Marker;
use tuja\data\model\Form as ModelForm;
use tuja\data\store\FormDao;
use tuja\util\FormUtils;
use tuja\util\FormView;
use WP_REST_Request;

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

		$form_dao = new FormDao();
		$forms    = $form_dao->get_all_in_competition( $group->competition_id );

		$available_forms = array_filter(
			$forms,
			function( ModelForm $form ) {
				return $form->is_submit_allowed();
			}
		);

		$form_utils = new FormUtils( $group );

		$available_form_views = array_map(
			function ( ModelForm $form ) use ( $form_utils ) {
				return $form_utils->get_form_view( $form, false, false, false );
			},
			$available_forms
		);

		// TODO: Extract to helper functions.
		$form_ids           = array();
		$question_group_ids = array();
		$question_ids       = array();
		array_walk(
			$available_form_views,
			function ( FormView $form_view ) use ( &$form_ids, &$question_group_ids, &$question_ids ) {
				$form_ids[] = $form_view->id;
				array_walk(
					$form_view->question_groups,
					function ( $question_group_view ) use ( &$question_group_ids, &$question_ids ) {
						$question_group_ids[] = $question_group_view['id'];
						array_walk(
							$question_group_view['questions'],
							function ( $question_view ) use ( &$question_ids ) {
								$question_ids[] = $question_view['id'];
							}
						);
					}
				);
			}
		);

		$available_markers = array_filter(
			$markers,
			function ( Marker $marker ) use ( $form_ids, $question_group_ids, $question_ids ) {
				$link_form_id           = isset( $marker->link_form_id ) ? intval( $marker->link_form_id ) : null;
				$link_question_group_id = isset( $marker->link_question_group_id ) ? intval( $marker->link_question_group_id ) : null;
				$link_form_question_id  = isset( $marker->link_form_question_id ) ? intval( $marker->link_form_question_id ) : null;

				$is_available = $marker->type !== Marker::MARKER_TYPE_TASK ||
				(
					( ! isset( $link_form_id ) || in_array( $link_form_id, $form_ids, true ) ) &&
					( ! isset( $link_question_group_id ) || in_array( $link_question_group_id, $question_group_ids, true ) ) &&
					( ! isset( $link_form_question_id ) || in_array( $link_form_question_id, $question_ids, true ) )
				);
				return $is_available;
			}
		);

		return array_values(
			array_map(
				function ( Marker $marker ) use ( $responses ) {
					return array(
						'type'                   => $marker->type,
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
				$available_markers
			)
		);
	}
}
