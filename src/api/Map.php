<?php

namespace tuja;

use tuja\data\store\GroupDao;
use tuja\data\store\MarkerDao;
use tuja\data\store\ResponseDao;
use tuja\data\model\Marker;
use tuja\data\model\Form as ModelForm;
use tuja\data\model\Group;
use tuja\data\store\FormDao;
use tuja\util\FormUtils;
use tuja\util\FormView;
use WP_REST_Request;

class AvailableFormObjects {
	public $form_ids           = array();
	public $question_group_ids = array();
	public $question_ids       = array();
}

class Map extends AbstractRestEndpoint {

	/**
	 * Return the identifiers for the forms, question groups and questions which the current group can see at this point in time.
	 *
	 * @param Group $group The group requesting the data.
	 */
	private static function get_available_form_object_ids( Group $group ): AvailableFormObjects {
		$objects  = new AvailableFormObjects();
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
				return $form_utils->get_form_view( $form, FormUtils::RETURN_NO_QUESTION_OBJECT, false );
			},
			$available_forms
		);

		array_walk(
			$available_form_views,
			function ( FormView $form_view ) use ( &$objects ) {
				$objects->form_ids[] = $form_view->id;
				array_walk(
					$form_view->question_groups,
					function ( $question_group_view ) use ( &$objects ) {
						$objects->question_group_ids[] = $question_group_view['id'];
						array_walk(
							$question_group_view['questions'],
							function ( $question_view ) use ( &$objects ) {
								$objects->question_ids[] = $question_view['id'];
							}
						);
					}
				);
			}
		);

		return $objects;
	}

	/**
	 * Get map markers which are either
	 *  - questions/question groups/forms which are available to the current user/group,
	 *  - "not tasks" (e.g. the "home marker").
	 *
	 * @param Group $group The group requesting the data.
	 */
	private static function get_markers_to_return( Group $group ) {
		$marker_dao           = new MarkerDao();
		$all_markers          = $marker_dao->get_all_on_map( $group->map_id );
		$available_object_ids = self::get_available_form_object_ids( $group );
		return array_filter(
			$all_markers,
			function ( Marker $marker ) use ( $available_object_ids ) {
				$link_form_id           = isset( $marker->link_form_id ) ? intval( $marker->link_form_id ) : null;
				$link_question_group_id = isset( $marker->link_question_group_id ) ? intval( $marker->link_question_group_id ) : null;
				$link_form_question_id  = isset( $marker->link_form_question_id ) ? intval( $marker->link_form_question_id ) : null;

				$is_available = $marker->type !== Marker::MARKER_TYPE_TASK ||
				(
					( ! isset( $link_form_id ) || in_array( $link_form_id, $available_object_ids->form_ids, true ) ) &&
					( ! isset( $link_question_group_id ) || in_array( $link_question_group_id, $available_object_ids->question_group_ids, true ) ) &&
					( ! isset( $link_form_question_id ) || in_array( $link_form_question_id, $available_object_ids->question_ids, true ) )
				);
				return $is_available;
			}
		);
	}

	public static function get_markers( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( $group === false ) {
			return self::create_response( 404 ); // Not Found.
		}

		if ( ! isset( $group->map_id ) ) {
			return self::create_response( 204 ); // No Content.
		}

		$response_dao = new ResponseDao();
		$responses    = $response_dao->get_latest_by_group( $group_id );

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
				self::get_markers_to_return( $group )
			)
		);
	}
}
