<?php


namespace tuja\frontend;


use tuja\data\model\Group;
use tuja\data\model\Response;
use tuja\data\store\ResponseDao;
use tuja\util\concurrency\LockValuesList;
use tuja\util\Strings;

class FormLockValidator {
	private $response_dao;
	private $group;

	public function __construct( ResponseDao $response_dao, Group $group ) {
		$this->response_dao = $response_dao;
		$this->group        = $group;
	}

	public function check_optimistic_lock( LockValuesList $update_locks ) {
		$update_ids = $update_locks->get_ids();
		$ref_locks  = $this->get_optimistic_lock_value( $update_ids );

		$valid_ids = $update_locks->get_valid_ids( $ref_locks );
		if ( count( $valid_ids ) !== count( $update_ids ) ) {
			throw new FormLockException( Strings::get( 'form.optimistic_lock_error' ), $update_locks->get_invalid_ids($ref_locks) );
		}
	}

	public function get_optimistic_lock_value( array $response_question_ids ): LockValuesList {

		$responses            = $this->response_dao->get_latest_by_group( $this->group->id );
		$response_by_question = array_combine( array_map( function ( Response $resp ) {
			return $resp->form_question_id;
		}, $responses ), $responses );

		return array_reduce( $response_question_ids, function ( LockValuesList $locks, $response_question_id ) use ( $response_by_question ) {
			if ( isset( $response_by_question[ $response_question_id ] ) ) {
				$response = $response_by_question[ $response_question_id ];
				if ( $response && ! is_null( $response->created ) ) {
					$locks->add_value( $response_question_id, $response->created->getTimestamp() );
				} else {
					$locks->add_value( $response_question_id, 0 );
				}
			} else {
				$locks->add_value( $response_question_id, 0 );
			}

			return $locks;
		}, new LockValuesList() );
	}


}