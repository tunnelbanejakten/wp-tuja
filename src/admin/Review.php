<?php

namespace tuja\admin;

use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\question\ImagesQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\model\Group;
use tuja\data\model\Response;
use tuja\data\store\CompetitionDao;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;


class Review {

	const DEFAULT_QUESTION_FILTER = ResponseDao::QUESTION_FILTER_UNREVIEWED_ALL;
	const RESPONSE_MISSING_ID = 0;

	private $competition;
	private $response_dao;

	const GROUP_FILTER_URL_PARAM = 'tuja_review_group_selector';
	const QUESTION_FILTER_URL_PARAM = 'tuja_question_filter';
	const QUESTION_FILTER_ALL = 'all';
	const QUESTION_FILTER_IMAGES = 'images';
	private $question_dao;
	private $question_group_dao;
	private $field_group_selector;

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

		$this->field_group_selector = new FieldGroupSelector( $this->competition );
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
				list( , $response_id, $question_id, $group_id ) = explode( '__', $field_name );
				if ( $response_id != self::RESPONSE_MISSING_ID ) {
					// Response exists, but is the response shown still the most recent one?

					if ( isset( $reviewable_responses_map[ $response_id ] ) ) {
						// Yes, this response can still be reviewed (it is the most recent one).

						$db_points->set(
							$reviewable_responses_map[ $response_id ]->group_id,
							$reviewable_responses_map[ $response_id ]->form_question_id,
							is_numeric( $field_value ) ? intval( $field_value ) : null );

						$reviewed_ids[] = $response_id;
					} else {
						$skipped ++;
					}
				} else {
					// Response did not exist when form was loaded but maybe one exists now?

					$is_response_submitted = count( array_filter( $reviewable_responses, function ( Response $response ) use ( $question_id, $group_id ) {
							return $response->group_id == $group_id && $response->form_question_id == $question_id;
						} ) ) > 0;

					if ( ! $is_response_submitted ) {
						// No, there is still no response from this team.

						$db_points->set(
							$group_id,
							$question_id,
							is_numeric( $field_value ) ? intval( $field_value ) : null );
					} else {
						$skipped ++;
					}
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

		$selected_filter = @$_GET[ Review::QUESTION_FILTER_URL_PARAM ] ?: self::DEFAULT_QUESTION_FILTER;

		$field_group_selector = $this->field_group_selector;

		$selected_groups = array_map( function ( Group $group ) {
			return $group->id;
		}, $field_group_selector->get_selected_groups( $_GET[ Review::GROUP_FILTER_URL_PARAM ] ) );

		$data = isset( $selected_filter ) ? $this->response_dao->get_by_questions( $competition->id, $selected_filter, $selected_groups ) : [];

		include( 'views/review.php' );
	}

	private function get_responses_to_review() {
		$all_questions = $this->question_dao->get_all_in_competition( $this->competition->id );

		// Get which questions to show responses for:
		$selected_question_ids = array_reduce(
			$all_questions,
			function ( $carry, AbstractQuestion $question ) {
				if ( @$_GET[ Review::QUESTION_FILTER_URL_PARAM ] == self::QUESTION_FILTER_IMAGES ) {
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
