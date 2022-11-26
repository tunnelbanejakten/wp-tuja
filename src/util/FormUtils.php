<?php

namespace tuja\util;

use DateTime;
use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\data\model\Event;
use tuja\data\model\Marker;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\store\EventDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MarkerDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\frontend\FormUserChanges;
use tuja\frontend\Form as FrontendForm;
use tuja\data\model\question\ImagesQuestion;
use tuja\util\ImageManager;
use tuja\util\score\ScoreCalculator;
use tuja\util\Template;


class FormView {
	public $id;
	public $name;
	public $is_read_only;
	public $question_groups;

	public function __construct( $id, $name, $is_read_only, $question_groups ) {
		$this->id              = $id;
		$this->name            = $name;
		$this->is_read_only    = $is_read_only;
		$this->question_groups = $question_groups;
	}
}

class FormUtils {
	const RETURN_DATABASE_QUESTION_OBJECT = 'RETURN_DATABASE_QUESTION_OBJECT';
	const RETURN_NO_QUESTION_OBJECT       = 'RETURN_NO_QUESTION_OBJECT';
	const RETURN_API_QUESTION_OBJECT      = 'RETURN_API_QUESTION_OBJECT';

	private $group;
	private $question_dao;
	private $question_group_dao;
	private $group_dao;
	private $response_dao;
	private $marker_dao;
	private $event_dao;
	private $competition_markers;
	private $group_events_cache    = null;
	private $group_responses_cache = null;

	function __construct( Group $group ) {
		$this->question_dao       = new QuestionDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->group_dao          = new GroupDao();
		$this->response_dao       = new ResponseDao();
		$this->marker_dao         = new MarkerDao();
		$this->event_dao          = new EventDao();
		$this->group              = $group;
	}

	public function get_form_view( Form $form, string $question_response_spec, bool $exclude_objects_with_marker ): FormView {
		$question_groups = array_filter(
			$this->question_group_dao->get_all_in_form( $form->id ),
			function ( QuestionGroup $question_group ) use ( $exclude_objects_with_marker ) {
				return ! $exclude_objects_with_marker || ! $this->is_marker_set( 'link_question_group_id', $question_group->id );
			}
		);
		return new FormView(
			intval( $form->id ),
			$form->name,
			! $form->is_submit_allowed(),
			array_map(
				function ( QuestionGroup $question_group ) use ( $question_response_spec, $exclude_objects_with_marker ) {
					return $this->get_question_group_view( $question_group, $question_response_spec, $exclude_objects_with_marker );
				},
				$question_groups
			),
		);
	}

	private function get_question_group_view( QuestionGroup $question_group, string $question_response_spec, bool $exclude_objects_with_marker ) {
		$all_questions = $question_group->get_filtered_questions( $this->question_dao, $this->group_dao, $this->group );

		$selected_questions = array_values(
			array_filter(
				$all_questions,
				function ( AbstractQuestion $question ) use ( $exclude_objects_with_marker ) {
					return ! $exclude_objects_with_marker || ! $this->is_marker_set( 'link_form_question_id', $question->id );
				}
			)
		);

		$questions_list = array_values(
			array_map(
				function ( AbstractQuestion $question ) use ( $question_response_spec ) {
					return array_merge(
						$question_response_spec === self::RETURN_API_QUESTION_OBJECT
							? $this->get_question_response( $question )
							: array(),
						$question_response_spec === self::RETURN_DATABASE_QUESTION_OBJECT
							? array( 'obj' => $question )
							: array(),
						array( 'id' => intval( $question->id ) )
					);
				},
				$selected_questions
			)
		);

		return array(
			'id'            => intval( $question_group->id ),
			'name'          => $question_group->text,

			// Parse Markdown texts into HTML.
			'description'   => isset( $question_group->text_description ) ? Template::string( $question_group->text_description )->render( array(), true ) : null,

			'is_marker_set' => $this->is_marker_set( 'link_question_group_id', $question_group->id ),
			'questions'     => $questions_list,
		);
	}

	private function is_marker_set( $object_type_marker_field, $object_id ) {
		if ( ! isset( $this->competition_markers ) ) {
			$this->competition_markers = $this->marker_dao->get_all_in_competition( $this->group->competition_id );
		}
		$matches = array_filter(
			$this->competition_markers,
			function ( Marker $marker ) use ( $object_type_marker_field, $object_id ) {
				return $marker->{$object_type_marker_field} === $object_id;
			}
		);
		return count( $matches ) > 0;
	}

	private function get_group_events() {
		if ( null === $this->group_events_cache ) {
			$this->group_events_cache = $this->event_dao->get_by_group( $this->group->id );
		}
		return $this->group_events_cache;
	}

	private function get_group_responses() {
		if ( null === $this->group_responses_cache ) {
			$this->group_responses_cache = $this->response_dao->get_latest_by_group( $this->group->id );
		}
		return $this->group_responses_cache;
	}

	public function get_question_response( AbstractQuestion $question ) {
		$form_key     = 'N/A';
		$form_handler = new FrontendForm( 'url', $this->group->random_id, $form_key );

		$optimistic_lock_field = FrontendForm::OPTIMISTIC_LOCK_FIELD_NAME;
		$optimistic_lock_value = $form_handler->get_optimistic_lock_value( array( $question->id ) );

		$response_field = $form_handler->get_response_field( $question->id );
		$all_responses  = $this->get_group_responses();
		$answer_object  = @$all_responses[ $question->id ]->submitted_answer ?: null;

		$tracked_answers_field = FrontendForm::TRACKED_ANSWERS_FIELD_NAME;
		$form_user_changes     = new FormUserChanges();
		$form_user_changes->track_answer( $question, $question->get_answer_object( $response_field, $answer_object, $this->group ) );
		$tracked_answers_value = $form_user_changes->get_tracked_answers_string();

		$response = array(
			'type'            => ( new \ReflectionClass( $question ) )->getShortName(),
			'response'        => array(
				'field_name'    => $response_field,
				'current_value' => $answer_object,
			),
			'optimistic_lock' => array(
				'field_name'    => $optimistic_lock_field,
				'current_value' => $optimistic_lock_value,
			),
			'tracked_answers' => array(
				'field_name'    => $tracked_answers_field,
				'current_value' => $tracked_answers_value,
			),
		);

		if ( $question instanceof ImagesQuestion ) {
			// Enrich data about images with thumbnail URLs (thumbnails will be generated if missing).
			$current_value = @$response['response']['current_value'] ?? array();
			$images        = @$current_value['images'] ?? array();
			$image_manager = new ImageManager();
			foreach ( $images as $index => $image_id ) {
				$url = $image_manager->get_resized_image_url( $image_id, 40000, $this->group->random_id );
				if ( $url !== false ) {
					$response['response']['current_value']['thumbnails'][ $index ] = $url;
				}
			}
		}

		$time_limit_adjusted = $question->get_adjusted_time_limit( $this->group );
		$is_timed_question   = $time_limit_adjusted > 0;
		$view_event          = array( 'is_required' => $is_timed_question );
		if ( $is_timed_question ) {
			$all_events             = $this->get_group_events();
			$start_countdown_events = array_filter(
				$all_events,
				function ( Event $event ) use ( $question ) {
					return Event::EVENT_VIEW === $event->event_name &&
					Event::OBJECT_TYPE_QUESTION === $event->object_type &&
					intval( $event->object_id ) === $question->id;
				}
			);

			$time_limit             = array( 'duration' => $time_limit_adjusted );
			$is_countdown_started   = count( $start_countdown_events ) > 0;
			$view_event['is_found'] = $is_countdown_started;
			if ( $is_countdown_started ) {
				$start_countdown_event               = current( $start_countdown_events );
				$now_timestamp                       = ( new DateTime( 'now' ) )->getTimestamp();
				$event_timestamp                     = $start_countdown_event->created_at->getTimestamp();
				$time_limit['started_at']            = $event_timestamp;
				$time_limit['ends_at']               = $event_timestamp + $time_limit_adjusted;
				$time_limit['duration_error_margin'] = ScoreCalculator::VIEW_EVENT_ERROR_MARGIN_SECONDS / 2; // To account for network delays and such.
				$time_limit['current_time']          = $now_timestamp;
				$response['config']                  = $question->get_public_properties();
			} else {
				$public_props       = $question->get_public_properties();
				$response['config'] = array(
					'name'             => @$public_props['name'] ?: null,
					'score_max'        => @$public_props['score_max'] ?: null,
					'text_preparation' => @$public_props['text_preparation'] ?: null,
				);
			}
			$response['time_limit'] = $time_limit;
		} else {
			$response['config'] = $question->get_public_properties();
		}
		$response['view_event'] = $view_event;

		if ( isset( $response['config']['text'] ) ) {
			// Parse Markdown texts into HTML.
			$response['config']['text'] = Template::string( $response['config']['text'] )->render( array(), true );
		}

		return $response;
	}
}
