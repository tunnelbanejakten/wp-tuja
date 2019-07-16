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
	private $review_component;
	private $selected_filter;
	private $selected_groups;

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
		$this->review_component     = new ReviewComponent( $this->competition );
		$this->selected_filter      = @$_GET[ Review::QUESTION_FILTER_URL_PARAM ] ?: self::DEFAULT_QUESTION_FILTER;
		$this->selected_groups      = $this->field_group_selector->get_selected_groups( $_GET[ Review::GROUP_FILTER_URL_PARAM ] );
	}


	public function handle_post() {
		if ( ! isset( $_POST['tuja_review_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_review_action'] === 'save' ) {

			$result = $this->review_component->handle_post( $this->selected_filter, $this->selected_groups );

			if ( $result['skipped'] > 0 ) {
				AdminUtils::printError( sprintf(
					'Kunde inte uppdatera poängen för %d frågor. Någon annan hann före.',
					$result['skipped'] ) );
			}
			if ( count( $result['marked_as_reviewed'] ) > 0 ) {
				AdminUtils::printSuccess( sprintf(
					'Svar på %d frågor har markerats som kontrollerade.',
					count( $result['marked_as_reviewed'] ) ) );
			}
		}
	}


	public function output() {
		$this->handle_post();

		$competition          = $this->competition;
		$review_component     = $this->review_component;
		$field_group_selector = $this->field_group_selector;
		$selected_filter      = $this->selected_filter;
		$selected_groups      = $this->selected_groups;

		include( 'views/review.php' );
	}

}
