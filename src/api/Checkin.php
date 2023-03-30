<?php

namespace tuja;

use Exception;
use Throwable;
use tuja\util\Strings;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PersonDao;
use tuja\frontend\GroupCheckin;
use tuja\controller\CheckinController;
use WP_REST_Request;

class Checkin extends AbstractRestEndpoint {

	public static function get_checkin_data( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( $group === false ) {
			return self::create_response( 404 );
		}
		$person_dao = new PersonDao();

		// If awaiting-checking status:
		// If checked-in status:
		if ( Group::STATUS_AWAITING_CHECKIN === $group->get_status() || Group::STATUS_CHECKEDIN === $group->get_status() ) {
			$category_dao = new GroupCategoryDao();
			$category     = $category_dao->get( $group->category_id );
			if ( $category === false ) {
				return self::create_response( 404 );
			}

			$participants = array_filter(
				$person_dao->get_all_in_group( $group->id ),
				function ( Person $person ) {
					return $person->is_attending();
				}
			);

			Strings::init( $group->competition_id );

			$template_parameters = GroupCheckin::params_body_text( $group );

			return array(
				'group_name'    => $group->name,
				'status'        => $group->get_status(),
				'category_name' => $category->name,

				'participants'  => array_map(
					function ( Person $person ) {
						return array(
							'status'              => $person->get_status(),
							'id'                  => $person->id,
							'random_id'           => $person->random_id,
							'name'                => $person->name,
							'phone'               => $person->phone,
							'is_competing'        => $person->is_competing(),
							'is_adult_supervisor' => $person->is_adult_supervisor(),
						);
					},
					$participants
				),

				'messages'      => array(
					'checkin_done'    => array(
						'title'     => Strings::get( 'checkin.yes.title' ),
						'body_text' => Strings::get( 'checkin.yes.body_text', $template_parameters ),
					),
					'something_wrong' => array(
						'title'     => Strings::get( 'checkin.no.title' ),
						'body_text' => Strings::get( 'checkin.no.body_text', $template_parameters ),
					),
				),
			);
		} else {
			return self::create_response( 204 );
		}
	}

	public static function check_in( WP_REST_Request $request ) {
		$token_decoded = $request->get_param( 'token_decoded' );

		$group_id = $token_decoded->group_id;

		$group_dao = new GroupDao();
		$group     = $group_dao->get( $group_id );
		if ( false === $group ) {
			return self::create_response( 404 );
		}

		$payload    = $request->get_json_params();
		$person_ids = array_map(
			'intval',
			@$payload['people_ids'] ?? array()
		);
		if ( empty( $person_ids ) ) {
			return self::create_response( 400 );
		}
		try {

			( new CheckinController() )->check_in( $group, $person_ids );
			return self::create_response( 204 );
		} catch ( Exception $e ) {
			return self::create_response( 409 );
		} catch ( Throwable $e ) {
			return self::create_response( 500 );
		}
	}
}
