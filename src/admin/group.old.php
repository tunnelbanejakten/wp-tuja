<?php

namespace tuja\admin;

use tuja\view\FieldImages;
use tuja\data\store\QuestionDao;
use tuja\data\store\PointsDao;
use tuja\util\score\ScoreCalculator;
use tuja\data\store\ResponseDao;
use tuja\data\store\GroupDao;

class Group {

	private $group;
	private $competition;

	public function __construct() {
		$this->group = $db_groups->get($_GET['tuja_group']);
		if (!$this->group) {
			print 'Could not find group';
			return;
		}

		$this->competition = $db_competition->get($this->group->competition_id);
		if (!$this->competition) {
			print 'Could not find competition';
			return;
		}
	}


	public function handle_post() {
		if ($_POST['tuja_points_action'] === 'save') {
			$db_question = new QuestionDao();
			$db_points = new PointsDao();

			$questions = $db_question->get_all_in_competition($this->competition->id);
			foreach($questions as $question) {
				$value = $_POST['tuja_group_points__' . $question->id];
				if(isset($value)) {
					$db_points->set($this->group->id, $question->id, is_numeric($value) ? intval($value) : null);
				}
			}
		}
	}


	public function output() {
		$this->handle_post();

		$competition_url = add_query_arg(array(
			'tuja_competition' => $this->competition->id,
			'tuja_view' => 'competition'
		));

		$forms = $db_form->get_all_in_competition($this->competition->id);
		$db_question = new QuestionDao();
		$db_response = new ResponseDao();
		$db_groups = new GroupDao();
		$db_points = new PointsDao();	

		$score_calculator = new ScoreCalculator($this->competition->id, $db_question, $db_response, $db_groups, $db_points);
		$calculated_scores_final = $score_calculator->score_per_question($this->group->id);
		$calculated_scores_without_overrides = $score_calculator->score_per_question($this->group->id, false);
		$responses = $db_response->get_latest_by_group($this->group->id);
		$response_per_question = array_combine(array_map(function($response) {
			return $response->form_question_id;
		}, $responses), array_values($responses));
		$points_overrides = $db_points->get_by_group($this->group->id);
		$points_overrides_per_question = array_combine(array_map(function ($points) {
			return $points->form_question_id;
		}, $points_overrides), array_values($points_overrides));
		
		include('views/group.php');
	}
}
