<?php

namespace tuja;

use tuja\data\store\GroupDao;
use tuja\data\store\MarkerDao;
use tuja\data\store\ResponseDao;
use tuja\data\model\Marker;
use tuja\data\model\Form as ModelForm;
use tuja\data\model\Group;
use tuja\data\model\Points;
use tuja\data\model\Ticket;
use tuja\data\store\FormDao;
use tuja\data\store\StationPointsDao;
use tuja\data\store\CompetitionDao;
use tuja\data\store\TicketDao;
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
		$marker_dao             = new MarkerDao();
		$all_markers            = $marker_dao->get_all_on_map( $group->map_id );
		$competition_dao        = new CompetitionDao();
		$competition            = $competition_dao->get( $group->competition_id );
		$is_competition_ongoing = $competition->is_ongoing();

		$available_object_ids = self::get_available_form_object_ids( $group );
		return array_filter(
			$all_markers,
			function ( Marker $marker ) use ( $available_object_ids, $is_competition_ongoing ) {
				$link_form_id           = isset( $marker->link_form_id ) ? intval( $marker->link_form_id ) : null;
				$link_question_group_id = isset( $marker->link_question_group_id ) ? intval( $marker->link_question_group_id ) : null;
				$link_form_question_id  = isset( $marker->link_form_question_id ) ? intval( $marker->link_form_question_id ) : null;
				$link_station_id        = isset( $marker->link_station_id ) ? intval( $marker->link_station_id ) : null;

				$is_available = $marker->type !== Marker::MARKER_TYPE_TASK ||
				(
					( ! isset( $link_form_id ) || in_array( $link_form_id, $available_object_ids->form_ids, true ) ) &&
					( ! isset( $link_question_group_id ) || in_array( $link_question_group_id, $available_object_ids->question_group_ids, true ) ) &&
					( ! isset( $link_form_question_id ) || in_array( $link_form_question_id, $available_object_ids->question_ids, true ) ) &&
					( ! isset( $link_station_id ) || $is_competition_ongoing )
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

		$station_points_dao = new StationPointsDao();
		$station_points_raw = $station_points_dao->get_by_group( $group_id );

		$ticket_dao = new TicketDao();
		$tickets    = $ticket_dao->get_group_tickets( $group );

		$station_points = array_combine(
			array_map(
				function ( Points $points ) {
					return $points->station_id;
				},
				$station_points_raw
			),
			$station_points_raw
		);

		return array_values(
			array_map(
				function ( Marker $marker ) use ( $responses, $station_points, $tickets ) {
					$link_station_id       = isset( $marker->link_station_id ) ? intval( $marker->link_station_id ) : null;
					$linked_station_ticket = isset( $marker->link_station_id ) ? ( current(
						array_filter(
							$tickets,
							function ( Ticket $ticket ) use ( $marker ) {
								return $ticket->station->id === $marker->link_station_id;
							}
						)
					) ?: null ) : null;
					$is_question_answered  = isset( $responses[ $marker->link_form_question_id ] );
					// $is_station_points_submitted and $is_ticket_used SHOULD have the same value but COULD be different,
					// for example if a crew member has submitted points for a team even though the team didn't have a ticket.
					$is_station_points_submitted = isset( $station_points[ $marker->link_station_id ] );
					$is_ticket_used              = isset( $linked_station_ticket ) ? $linked_station_ticket->is_used : false;
					return array(
						'type'                   => $marker->type,
						'latitude'               => $marker->gps_coord_lat,
						'longitude'              => $marker->gps_coord_long,
						'radius'                 => 25,
						'name'                   => $marker->name,
						'link_duel_group_id'     => isset( $marker->link_duel_group_id ) ? intval( $marker->link_duel_group_id ) : null,
						'link_duel_group_name'   => $marker->link_duel_group_name,
						'link_form_id'           => isset( $marker->link_form_id ) ? intval( $marker->link_form_id ) : null,
						'link_form_question_id'  => isset( $marker->link_form_question_id ) ? intval( $marker->link_form_question_id ) : null,
						'link_question_group_id' => isset( $marker->link_question_group_id ) ? intval( $marker->link_question_group_id ) : null,
						'link_station_id'        => $link_station_id,
						'link_station_ticket'    => $linked_station_ticket,
						'is_done'                => $is_question_answered || $is_station_points_submitted || $is_ticket_used,
					);
				},
				self::get_markers_to_return( $group )
			)
		);
	}
}
