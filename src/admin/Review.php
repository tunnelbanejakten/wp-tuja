<?php

namespace tuja\admin;

use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\question\ImagesQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\model\Response;
use tuja\data\store\CompetitionDao;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;


class Review {

	private $competition;
	private $response_dao;

	const QUESTION_FILTER_URL_PARAM = 'tuja_question_filter';
	const QUESTION_FILTER_ALL = 'all';
	const QUESTION_FILTER_IMAGES = 'images';
	private $question_dao;
	private $question_group_dao;

	public function __construct() {
		$this->question_dao = new QuestionDao();
		$this->response_dao = new ResponseDao();
		$this->question_group_dao = new QuestionGroupDao();
		$db_competition     = new CompetitionDao();

		$this->competition = $db_competition->get( $_GET['tuja_competition'] );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}


	public function handle_post() {
		if(!isset($_POST['tuja_review_action'])) return;

		$db_response = new ResponseDao();
		$db_points   = new PointsDao();

		if ( $_POST['tuja_review_action'] === 'save' ) {
			//
			// Get information about responses we WANT to update:
			//
			$form_values = array_filter( $_POST, function ( $key ) {
				return substr( $key, 0, strlen( 'tuja_review_points' ) ) === 'tuja_review_points';
			}, ARRAY_FILTER_USE_KEY );

			//
			// Get information about responses we CAN update:
			//
			$reviewable_responses = $this->get_responses_to_review();

			$reviewable_responses_map = array_combine( array_map( function ( Response $response ) {
				return $response->id;
			}, $reviewable_responses ), $reviewable_responses );

			//
			// Perform updates:
			//
			$skipped      = 0;
			$reviewed_ids = [];
			foreach ( $form_values as $field_name => $field_value ) {
				list( , $response_id ) = explode( '__', $field_name );
				if ( isset( $reviewable_responses_map[ $response_id ] ) ) {
					// Yes, this response can still be reviewed.
					$db_points->set(
						$reviewable_responses_map[ $response_id ]->group_id,
						$reviewable_responses_map[ $response_id ]->form_question_id,
						is_numeric( $field_value ) ? intval( $field_value ) : null );
					$reviewed_ids[] = $response_id;
				} else {
					$skipped ++;
				}
			}
			$db_response->mark_as_reviewed( $reviewed_ids );

			//
			// Show warning message if some points/changes could not be saved:
			//
			if ( $skipped > 0 ) {
				$error_message = sprintf(
					'Kunde inte uppdatera poängen för %d frågor. Någon annan hann före.',
					$skipped );

				AdminUtils::printError( $error_message );
			}
		}
	}


	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		$db_groups   = new GroupDao();
		$db_form     = new FormDao();
		$db_response = $this->response_dao;
		$db_points   = new PointsDao();
		$db_question = $this->question_dao;

		$question_groups = $this->question_group_dao->get_all_in_competition( $competition->id );
		$question_groups = array_combine( array_map( function ( QuestionGroup $qg ) {
			return $qg->id;
		}, $question_groups ), $question_groups );

		$groups     = $db_groups->get_all_in_competition( $competition->id );
		$groups_map = array_combine( array_map( function ( $group ) {
			return $group->id;
		}, $groups ), array_values( $groups ) );
		$forms      = $db_form->get_all_in_competition( $competition->id );

		$responses = $this->get_responses_to_review();

		$question_filters = [
			[
				'key'      => self::QUESTION_FILTER_ALL,
				'selected' => ! isset( $_GET[ Review::QUESTION_FILTER_URL_PARAM ] ) || $_GET[ Review::QUESTION_FILTER_URL_PARAM ] == self::QUESTION_FILTER_ALL,
				'label'    => 'Alla'
			],
			[
				'key'      => self::QUESTION_FILTER_IMAGES,
				'selected' => $_GET[ Review::QUESTION_FILTER_URL_PARAM ] == self::QUESTION_FILTER_IMAGES,
				'label'    => 'Enbart bilder'
			]
		];

		$current_points = $db_points->get_by_competition( $competition->id );
		$current_points = array_combine(
			array_map( function ( $points ) {
				return $points->form_question_id . '__' . $points->group_id;
			}, $current_points ),
			array_values( $current_points )
		);

		include( 'views/review.php' );
	}

	private function get_responses_to_review() {
		$all_questions = $this->question_dao->get_all_in_competition( $this->competition->id );

		// Get which questions to show responses for:
		$selected_question_ids = array_reduce(
			$all_questions,
			function ( $carry, AbstractQuestion $question ) {
				if ( $_GET[ Review::QUESTION_FILTER_URL_PARAM ] == self::QUESTION_FILTER_IMAGES ) {
					if ( $question instanceof ImagesQuestion) {
						$carry[] = $question->id;
					}
				} else {
					$carry[] = $question->id;
				}

				return $carry;
			},
			[] );

		// Get non-reviewed responses for questions in $selected_question_ids
		$responses = array_filter(
			$this->response_dao->get_not_reviewed( $this->competition->id ),
			function ( Response $response ) use ( $selected_question_ids ) {
				return in_array( $response->form_question_id, $selected_question_ids );
			} );

		return $responses;
	}
}
