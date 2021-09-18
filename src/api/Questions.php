<?php

namespace tuja;

use tuja\data\model\Event;
use tuja\data\model\Form as ModelForm;
use tuja\data\store\EventDao;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\frontend\FormUserChanges;
use tuja\frontend\Form as FrontendForm;
use tuja\util\FormUtils;
use WP_REST_Request;

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

		$form_utils = new FormUtils( $group );
		return $form_utils->get_question_response( $question, $events, $responses );
	}

	public static function get_all_questions( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( $group === false ) {
			return self::create_response( 404 );
		}

		$form_dao = new FormDao();
		$forms    = $form_dao->get_all_in_competition( $group->competition_id );

		$available_forms = array_filter(
			$forms,
			function( ModelForm $form ) {
				return $form->is_submit_allowed();
			}
		);

		$form_utils = new FormUtils( $group );

		return array_values(
			array_map(
				function ( ModelForm $form ) use ( $form_utils ) {
					return $form_utils->get_form_view( $form, FormUtils::RETURN_API_QUESTION_OBJECT, true );
				},
				$available_forms
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

		$form_handler = new FrontendForm( 'url', $group->random_id, $form_key );
		$errors       = $form_handler->update_answers( $group->id );

		if ( count( $errors ) === 0 ) {
			$response_dao = new ResponseDao();
			$responses    = $response_dao->get_latest_by_group( $group->id );
			$form_utils   = new FormUtils( $group );
			return $form_utils->get_question_response( $question, array(), $responses );
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
