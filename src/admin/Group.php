<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Event;
use tuja\data\model\QuestionGroup;
use tuja\data\store\CompetitionDao;
use tuja\data\store\EventDao;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MessageDao;
use tuja\data\store\PersonDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\frontend\router\FormInitiator;
use tuja\frontend\router\GroupCheckinInitiator;
use tuja\frontend\router\GroupEditorInitiator;
use tuja\frontend\router\GroupSignupInitiator;
use tuja\frontend\router\PointsOverrideInitiator;
use tuja\util\score\ScoreCalculator;
use tuja\util\JwtUtils;

class Group {

	const DEFAULT_QUESTION_FILTER   = ResponseDao::QUESTION_FILTER_ALL;
	const QUESTION_FILTER_URL_PARAM = 'tuja_group_question_filter';

	private $group;
	private $competition;
	private $group_dao;
	private $question_group_dao;
	private $review_component;
	private $form_dao;

	public function __construct() {
		$this->group_dao          = new GroupDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->group              = $this->group_dao->get( $_GET['tuja_group'] );
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
		$this->review_component = new ReviewComponent( $this->competition );
		$this->form_dao         = new FormDao();
	}


	public function handle_post() {
		if ( ! isset( $_POST['tuja_points_action'] ) ) {
			return;
		}

		list( $action, $parameter ) = explode( '__', $_POST['tuja_points_action'] );

		if ( $action === 'save' ) {

			$result = $this->review_component->handle_post(
				$_GET[ self::QUESTION_FILTER_URL_PARAM ] ?: self::DEFAULT_QUESTION_FILTER,
				[ $this->group ] );

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
		} elseif ( $action === 'transition' ) {

			$this->group->set_status( $parameter );

			$success = $this->group_dao->update( $this->group );

			if ( $success ) {
				AdminUtils::printSuccess( sprintf(
					'Status har ändrats till %s.',
					$this->group->get_status() ) );
			} else {
				AdminUtils::printError( sprintf(
					'Kunde inte ändra till %s.',
					$parameter ) );
			}

		} elseif ( $action === 'move_people' ) {

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
		} elseif ( $action === 'delete_event' ) {
			$event_dao     = new EventDao();
			$affected_rows = $event_dao->delete( $parameter );

			$success = $affected_rows !== false && $affected_rows === 1;

			if ( $success ) {
				AdminUtils::printSuccess( 'Händelsen att frågan har visats har tagits bort' );
			} else {
				AdminUtils::printError( 'Kunde inte ta bort händelsen.' );
				if ( $error = $wpdb->last_error ) {
					AdminUtils::printError( $error );
				}
			}
		}
	}

	public function get_scripts(): array {
		return [
			'admin-review-component.js'
		];
	}

	public function output() {
		$this->handle_post();

		$messages_manager = new MessagesManager( $this->competition );
		$messages_manager->handle_post();

		$competition      = $this->competition;
		$review_component = $this->review_component;

		$db_form           = new FormDao();
		$forms             = $db_form->get_all_in_competition( $competition->id );
		$db_question       = new QuestionDao();
		$db_question_group = new QuestionGroupDao();
		$db_response       = new ResponseDao();
		$db_groups         = new GroupDao();
		$db_points         = new PointsDao();
		$db_message        = new MessageDao();

		$question_groups = $this->question_group_dao->get_all_in_competition( $competition->id );
		$question_groups = array_combine( array_map( function ( QuestionGroup $qg ) {
			return $qg->id;
		}, $question_groups ), $question_groups );

		$group = $this->group;

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
		}, $responses ), array_values( $responses ) );
		$points_overrides              = $db_points->get_by_group( $group->id );
		$points_overrides_per_question = array_combine( array_map( function ( $points ) {
			return $points->form_question_id;
		}, $points_overrides ), array_values( $points_overrides ) );

		$person_dao = new PersonDao();
		$people     = $person_dao->get_all_in_group( $group->id, true );

		$registration_evaluation = $group->evaluate_registration();

		$groups = $db_groups->get_all_in_competition( $competition->id, true );

		$group_signup_link  = GroupSignupInitiator::link( $group );
		$group_editor_link  = GroupEditorInitiator::link( $group );
		$group_checkin_link = GroupCheckinInitiator::link( $group );

		$group_form_links = array_map( function ( \tuja\data\model\Form $form ) use ( $group ) {
			if ( $group->get_category()->get_rules()->is_crew() ) {
				return sprintf( '<p>Länkar för att rapportering in poäng för formulär %s: <a href="%s">%s</a></p>',
					$form->name,
					PointsOverrideInitiator::link( $group, $form->id ),
					PointsOverrideInitiator::link( $group, $form->id ) );
			} else {
				return sprintf( '<p>Länkar för att svara på formulär %s: <a href="%s">%s</a></p>',
					$form->name,
					FormInitiator::link( $group, $form ),
					FormInitiator::link( $group, $form ) );
			}
		}, $this->form_dao->get_all_in_competition( $competition->id ) );

		$token = JwtUtils::create_token( $competition->id, $group->id );

		$event_dao            = new EventDao();
		$view_question_events = array_filter(
			$event_dao->get_by_group( $competition->id, $group->id ),
			function ( Event $event ) {
				return $event->event_name === Event::EVENT_VIEW && $event->object_type === Event::OBJECT_TYPE_QUESTION;
			}
		);

		include 'views/group.php';
	}
}
