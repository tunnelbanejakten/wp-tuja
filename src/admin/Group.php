<?php

namespace tuja\admin;

use Exception;
use tuja\data\store\CompetitionDao;
use tuja\data\store\FormDao;
use tuja\data\store\MessageDao;
use tuja\data\store\PersonDao;
use tuja\data\store\QuestionGroupDao;
use tuja\util\rules\RegistrationEvaluator;
use tuja\data\store\QuestionDao;
use tuja\data\store\PointsDao;
use tuja\util\score\ScoreCalculator;
use tuja\data\store\ResponseDao;
use tuja\data\store\GroupDao;

class Group {

	private $group;
	private $competition;
	private $group_dao;

	public function __construct() {
		$this->group_dao = new GroupDao();
		$this->group     = $this->group_dao->get( $_GET['tuja_group'] );
		if ( ! $this->group ) {
			print 'Could not find group';

			return;
		}

		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $this->group->competition_id );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}


	public function handle_post() {
		if ( ! isset( $_POST['tuja_points_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_points_action'] === 'save' ) {
			$db_question = new QuestionDao();
			$db_points   = new PointsDao();

			$questions = $db_question->get_all_in_competition( $this->competition->id );
			foreach ( $questions as $question ) {
				$value = $_POST[ 'tuja_group_points__' . $question->id ];
				if ( isset( $value ) ) {
					$db_points->set( $this->group->id, $question->id, is_numeric( $value ) ? intval( $value ) : null );
				}
			}
		} elseif ( $_POST['tuja_points_action'] === 'move_people' ) {

			if ( ! isset( $_POST['tuja_group_people'] ) || ! is_array( $_POST['tuja_group_people'] ) ) {
				AdminUtils::printError( 'No people choosen.' );

				return;
			}

			if ( ! isset( $_POST['tuja_group_move_people_to'] ) || ! is_numeric( $_POST['tuja_group_move_people_to'] ) ) {
				AdminUtils::printError( 'No group choosen.' );

				return;
			}

			$move_to_group = $this->group_dao->get( intval( $_POST['tuja_group_move_people_to'] ) );

			if ( ! isset( $_POST['tuja_group_people'] ) || ! is_array( $_POST['tuja_group_people'] ) || $move_to_group === false ) {
				AdminUtils::printError( 'No people choosen.' );

				return;
			}

			foreach ( $_POST['tuja_group_people'] as $person_id ) {
				$person_dao       = new PersonDao();
				$person           = $person_dao->get( $person_id );
				$person->group_id = $move_to_group->id;
				try {
					$affected_rows = $person_dao->update( $person );
					if ( $affected_rows === false ) {
						AdminUtils::printError( sprintf( 'Could not move %s to %s.', $person->name, $move_to_group->name ) );
					}
				} catch ( Exception $e ) {
					AdminUtils::printException( $e );
				}
			}
		}
	}


	public function output() {
		$this->handle_post();

		$messages_manager = new MessagesManager($this->competition);
		$messages_manager->handle_post();

		$competition = $this->competition;

		$db_form           = new FormDao();
		$forms             = $db_form->get_all_in_competition( $competition->id );
		$db_question       = new QuestionDao();
		$db_question_group = new QuestionGroupDao();
		$db_response       = new ResponseDao();
		$db_groups         = new GroupDao();
		$db_points         = new PointsDao();
		$db_message        = new MessageDao();

		$group                         = $this->group;

		$score_calculator = new ScoreCalculator(
			$competition->id,
			$db_question,
			$db_question_group,
			$db_response,
			$db_groups,
			$db_points
		);
		$score_result     = $score_calculator->score( $group->id );

		$responses                     = $db_response->get_latest_by_group( $group->id );
		$response_per_question         = array_combine( array_map( function ( $response ) {
			return $response->form_question_id;
		}, $responses), array_values($responses));
		$points_overrides              = $db_points->get_by_group( $group->id );
		$points_overrides_per_question = array_combine( array_map( function ( $points ) {
			return $points->form_question_id;
		}, $points_overrides ), array_values( $points_overrides ) );

		$person_dao = new PersonDao();
		$people     = $person_dao->get_all_in_group( $group->id );

		$registration_evaluator  = new RegistrationEvaluator();
		$registration_evaluation = $registration_evaluator->evaluate( $group );

		$groups                              = $db_groups->get_all_in_competition( $competition->id );

		include( 'views/group.php' );
	}
}
