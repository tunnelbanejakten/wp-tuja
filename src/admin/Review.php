<?php

namespace tuja\admin;

use tuja\data\store\CompetitionDao;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;

class Review {

	private $competition;

	public function __construct() {
		$db_competition = new CompetitionDao();

		$this->competition = $db_competition->get( $_GET['tuja_competition'] );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}


	public function handle_post() {
		if ( $_POST['tuja_review_action'] === 'save' ) {
			$form_values = array_filter( $_POST, function ( $key ) {
				return substr( $key, 0, strlen( 'tuja_review_points' ) ) === 'tuja_review_points';
			}, ARRAY_FILTER_USE_KEY );

			foreach ( $form_values as $field_name => $field_value ) {
				list( , $question_id, $group_id ) = explode( '__', $field_name );
				$db_points->set( $group_id, $question_id, is_numeric( $field_value ) ? intval( $field_value ) : null );
			}
			$db_response->mark_as_reviewed( explode( ',', $_POST['tuja_review_response_ids'] ) );
		}
	}


	public function output() {
		$this->handle_post();

		$competition_url = add_query_arg( array(
			'tuja_competition' => $this->competition->id,
			'tuja_view'        => 'competition'
		) );

		$db_groups   = new GroupDao();
		$db_form     = new FormDao();
		$db_response = new ResponseDao();
		$db_points   = new PointsDao();
		$db_question = new QuestionDao();

		$groups     = $db_groups->get_all_in_competition( $this->competition->id );
		$groups_map = array_combine( array_map( function ( $group ) {
			return $group->id;
		}, $groups ), array_values( $groups ) );
		$forms      = $db_form->get_all_in_competition( $this->competition->id );

		$responses = $db_response->get_not_reviewed( $this->competition->id );

		$current_points = $db_points->get_by_competition( $this->competition->id );
		$current_points = array_combine(
			array_map( function ( $points ) {
				return $points->form_question_id . '__' . $points->group_id;
			}, $current_points ),
			array_values( $current_points )
		);

		include( 'views/review.php' );
	}
}
