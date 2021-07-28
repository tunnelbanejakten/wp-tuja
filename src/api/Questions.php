<?php

namespace tuja;

use Reflection;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;
use tuja\frontend\Form;
use tuja\util\JwtUtils;
use tuja\util\ReflectionUtils;
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

		$form_key     = 'N/A';
		$form_handler = new Form( 'url', $group->random_id, $form_key );

		$response_dao   = new ResponseDao();
		$responses      = $response_dao->get_latest_by_group( $group_id );
		$response_field = $form_handler->get_response_field( $question->id );
		$response_value = @$responses[ $question->id ]->submitted_answer ?: null;

		$optimistic_lock_field = Form::OPTIMISTIC_LOCK_FIELD_NAME;
		$optimistic_lock_value = $form_handler->get_optimistic_lock_value( array( $question->id ) );

		return array(
			'type'            => ( new \ReflectionClass( $question ) )->getShortName(),
			'config'          => $question->get_public_properties(),
			'is_read_only'    => null,
			'response'        => array(
				'field_name'    => $response_field,
				'current_value' => $response_value,
			),
			'optimistic_lock' => array(
				'field_name'    => $optimistic_lock_field,
				'current_value' => $optimistic_lock_value,
			),
		);
	}
}
