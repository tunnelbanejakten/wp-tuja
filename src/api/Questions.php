<?php

namespace tuja;

use DateTime;
use Reflection;
use tuja\data\model\Event;
use tuja\data\model\Group;
use tuja\data\model\Marker;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\question\ImagesQuestion;
use tuja\data\store\EventDao;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MarkerDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\frontend\Form;
use tuja\util\Template;
use tuja\util\JwtUtils;
use tuja\util\ReflectionUtils;
use tuja\frontend\FormUserChanges;
use tuja\util\ImageManager;
use tuja\util\score\ScoreCalculator;
use WP_REST_Request;
use WP_REST_Response;

class Questions extends AbstractRestEndpoint {

	public static function get_question( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( $group === false ) {
			return self::create_response( 404 );
		}

		$question_id  = $request->get_param( 'id' );
		$question_dao = new QuestionDao();
		$question     = $question_dao->get( $question_id );
		if ( $question === false ) {
			return self::create_response( 404 );
		}

		$response_dao = new ResponseDao();
		$responses    = $response_dao->get_latest_by_group( $group_id );

		$event_dao = new EventDao();
		$events    = $event_dao->get_by_group( $group->id );

		return self::get_question_response(
			$question,
			$group,
			$events,
			$responses
		);
	}

	private static function get_question_response(
		AbstractQuestion $question,
		Group $group,
		array $all_group_events,
		array $all_responses
	) {

		$form_key     = 'N/A';
		$form_handler = new Form( 'url', $group->random_id, $form_key );

		$optimistic_lock_field = Form::OPTIMISTIC_LOCK_FIELD_NAME;
		$optimistic_lock_value = $form_handler->get_optimistic_lock_value( array( $question->id ) );

		$response_field = $form_handler->get_response_field( $question->id );
		$answer_object  = @$all_responses[ $question->id ]->submitted_answer ?: null;

		$tracked_answers_field = Form::TRACKED_ANSWERS_FIELD_NAME;
		$form_user_changes     = new FormUserChanges();
		$form_user_changes->track_answer( $question, $question->get_answer_object( $response_field, $answer_object ) );
		$tracked_answers_value = $form_user_changes->get_tracked_answers_string();

		$response = array(
			'type'            => ( new \ReflectionClass( $question ) )->getShortName(),
			'is_read_only'    => null,
			'response'        => array(
				'field_name'    => $response_field,
				'current_value' => $answer_object,
			),
			'optimistic_lock' => array(
				'field_name'    => $optimistic_lock_field,
				'current_value' => $optimistic_lock_value,
			),
			'tracked_answers' => array(
				'field_name'    => $tracked_answers_field,
				'current_value' => $tracked_answers_value,
			),
		);

		if ( $question instanceof ImagesQuestion ) {
			// Enrich data about images with thumbnail URLs (thumbnails will be generated if missing).
			$images        = @$response['response']['current_value']['images'];
			$image_manager = new ImageManager();
			foreach ( $images as $index => $image_id ) {
				$url = $image_manager->get_resized_image_url( $image_id, 40000, $group->random_id );
				if ( $url !== false ) {
					$response['response']['current_value']['thumbnails'][ $index ] = $url;
				}
			}
		}

		$is_view_event_required = $question->limit_time > 0;
		$response['view_event'] = array( 'is_required' => $is_view_event_required );
		if ( $is_view_event_required ) {
			$all_events = $all_group_events;
			$events     = array_filter(
				$all_events,
				function ( Event $event ) use ( $question ) {
					return $event->event_name === Event::EVENT_VIEW &&
						$event->object_type === Event::OBJECT_TYPE_QUESTION &&
						$event->object_id === $question->id;
				}
			);

			$is_view_event_found                = count( $events ) > 0;
			$response['view_event']['is_found'] = $is_view_event_found;
			$response['limit_time_max']         = $question->limit_time;
			if ( $is_view_event_found ) {
				$view_event                       = current( $events );
				$time_passed                      = ( new DateTime( 'now' ) )->getTimestamp() - $view_event->created_at->getTimestamp();
				$time_remaining_error_margin      = ScoreCalculator::VIEW_EVENT_ERROR_MARGIN_SECONDS / 2; // To account for network delays and such.
				$time_remaining                   = $question->limit_time - $time_passed + $time_remaining_error_margin;
				$response['limit_time_remaining'] = max( 0, $time_remaining ); // No point in returing negative values.
				$response['config']               = $question->get_public_properties();
			} else {
				$response['config'] = null;
			}
		} else {
			$response['config'] = $question->get_public_properties();
		}

		if ( isset( $response['config']['text'] ) ) {
			// Parse Markdown texts into HTML.
			$response['config']['text'] = Template::string( $response['config']['text'] )->render( array(), true );
		}

		return $response;
	}

	public static function get_all_questions( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( $group === false ) {
			return self::create_response( 404 );
		}

		// Get all questions.

		$question_dao  = new QuestionDao();
		$all_questions = $question_dao->get_all_in_competition( $group->competition_id );

		// Remove questions with GPS coordinates.
		$marker_dao = new MarkerDao();
		$markers    = $marker_dao->get_all_in_competition( $group->competition_id );

		$selected_questions = array_values(
			array_filter(
				$all_questions,
				function ( AbstractQuestion $question ) use ( $markers ) {
					$matching_markers = array_filter(
						$markers,
						function ( Marker $marker ) use ( $question ) {
							return $marker->link_form_question_id === $question->id ||
							$marker->link_question_group_id === $question->question_group_id;
						}
					);
					return empty( $matching_markers );
				}
			)
		);

		// Enrich with "time limit" properties.
		$response_dao = new ResponseDao();
		$responses    = $response_dao->get_latest_by_group( $group_id );

		$event_dao = new EventDao();
		$events    = $event_dao->get_by_group( $group->id );

		return array_values(
			array_map(
				function ( AbstractQuestion $question ) use ( $group, $events, $responses ) {
					return array_merge(
						self::get_question_response( $question, $group, $events, $responses ),
						array( 'id' => intval( $question->id ) )
					);
				},
				$selected_questions
			)
		);
	}

	public static function post_answer( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( $group === false ) {
			return self::create_response( 404 );
		}

		$question_id  = $request->get_param( 'id' );
		$question_dao = new QuestionDao();
		$question     = $question_dao->get( $question_id );
		if ( $question === false ) {
			return self::create_response( 404 );
		}

		$question_group_dao = new QuestionGroupDao();
		$question_group     = $question_group_dao->get( $question->question_group_id );

		$form_dao = new FormDao();
		$form     = $form_dao->get( $question_group->form_id );
		$form_key = $form->random_id;

		$form_handler = new Form( 'url', $group->random_id, $form_key );
		$errors       = $form_handler->update_answers( $group->id );

		if ( count( $errors ) === 0 ) {
			return self::create_response( 204 );
		} else {
			return self::create_response( 400 );
		}
	}

	public static function post_view_event( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( $group === false ) {
			return self::create_response( 404 );
		}

		$question_id  = $request->get_param( 'id' );
		$question_dao = new QuestionDao();
		$question     = $question_dao->get( $question_id );
		if ( $question === false ) {
			return self::create_response( 404 );
		}

		$event_dao = new EventDao();

		$event                 = new Event();
		$event->competition_id = $group->competition_id;
		$event->event_name     = Event::EVENT_VIEW;
		$event->event_data     = null;
		$event->group_id       = $group->id;
		$event->person_id      = null;
		$event->object_type    = Event::OBJECT_TYPE_QUESTION;
		$event->object_id      = $question->id;

		$result = $event_dao->create( $event );
		if ( $result === false ) {
			return self::create_response( 500 );
		}

		return self::create_response( 201 );
	}
}
