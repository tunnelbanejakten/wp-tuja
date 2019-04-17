<?php

namespace tuja\admin;

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

	public function __construct() {
		$db_groups   = new GroupDao();
		$this->group = $db_groups->get($_GET['tuja_group']);
		if (!$this->group) {
			print 'Could not find group';

			return;
		}

		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get($this->group->competition_id);
		if (!$this->competition) {
			print 'Could not find competition';

			return;
		}
	}


	public function handle_post() {
		if(!isset($_POST['tuja_points_action'])) return;

		if ( $_POST['tuja_points_action'] === 'save' ) {
			$db_question = new QuestionDao();
			$db_points   = new PointsDao();

			$questions = $db_question->get_all_in_competition($this->competition->id);
			foreach($questions as $question) {
				$value = $_POST[ 'tuja_group_points__' . $question->id ];
				if(isset($value)) {
					$db_points->set($this->group->id, $question->id, is_numeric($value) ? intval($value) : null);
				}
			}
		}
	}


	public function output() {
		$this->handle_post();

		$competition     = $this->competition;

		$db_form           = new FormDao();
		$forms             = $db_form->get_all_in_competition( $competition->id );
		$db_question       = new QuestionDao();
		$db_question_group = new QuestionGroupDao();
		$db_response       = new ResponseDao();
		$db_groups         = new GroupDao();
		$db_points         = new PointsDao();
		$db_message        = new MessageDao();

		$group                               = $this->group;

		$score_calculator                    = new ScoreCalculator( $competition->id, $db_question, $db_question_group, $db_response, $db_groups, $db_points );
		// TODO: Return ScoreResult with all data from score() instead of having two different methods with different arguments.
		$calculated_scores_final             = $score_calculator->score_per_question( $group->id );
		$calculated_scores_without_overrides = $score_calculator->score_per_question( $group->id, false );
		$questions_score                     = $score_calculator->score( $group->id, false );
		$final_score                         = $score_calculator->score( $group->id, true );

		$responses                           = $db_response->get_latest_by_group( $group->id );
		$response_per_question               = array_combine( array_map( function ( $response ) {
			return $response->form_question_id;
		}, $responses), array_values($responses));
		$points_overrides                    = $db_points->get_by_group( $group->id );
		$points_overrides_per_question       = array_combine( array_map( function ( $points ) {
			return $points->form_question_id;
		}, $points_overrides), array_values($points_overrides));

		$person_dao = new PersonDao();
		$people = $person_dao->get_all_in_group( $group->id );

		$registration_evaluator  = new RegistrationEvaluator();
		$registration_evaluation = $registration_evaluator->evaluate( $group );

		include('views/group.php');
	}
}
