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
	public $question_groups;

	public function __construct( $id, $name, $question_groups ) {
		$this->id              = $id;
		$this->name            = $name;
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

		// Enrich with "time limit" properties.
		$responses = $this->response_dao->get_latest_by_group( $this->group->id );

		$events = $this->event_dao->get_by_group( $this->group->id );

		$questions_list = array_values(
			array_map(
				function ( AbstractQuestion $question ) use ( $events, $responses, $question_response_spec ) {
					return array_merge(
						$question_response_spec === self::RETURN_API_QUESTION_OBJECT
							? $this->get_question_response( $question, $events, $responses )
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

	public function get_question_response(
		AbstractQuestion $question,
		array $all_group_events,
		array $all_responses
	) {

		$form_key     = 'N/A';
		$form_handler = new FrontendForm( 'url', $this->group->random_id, $form_key );

		$optimistic_lock_field = FrontendForm::OPTIMISTIC_LOCK_FIELD_NAME;
		$optimistic_lock_value = $form_handler->get_optimistic_lock_value( array( $question->id ) );

		$response_field = $form_handler->get_response_field( $question->id );
		$answer_object  = @$all_responses[ $question->id ]->submitted_answer ?: null;

		$tracked_answers_field = FrontendForm::TRACKED_ANSWERS_FIELD_NAME;
		$form_user_changes     = new FormUserChanges();
		$form_user_changes->track_answer( $question, $question->get_answer_object( $response_field, $answer_object ) );
		$tracked_answers_value = $form_user_changes->get_tracked_answers_string();

		$response = array(
			'type'            => ( new \ReflectionClass( $question ) )->getShortName(),
			'is_read_only'    => null,
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

		$time_limit_adjusted    = $question->get_adjusted_time_limit( $this->group );
		$is_view_event_required = $time_limit_adjusted > 0;
		$response['view_event'] = array( 'is_required' => $is_view_event_required );
		if ( $is_view_event_required ) {
			$all_events = $all_group_events;
			$events     = array_filter(
				$all_events,
				function ( Event $event ) use ( $question ) {
					return $event->event_name === Event::EVENT_VIEW &&
						$event->object_type === Event::OBJECT_TYPE_QUESTION &&
						$event->object_id === $question->id;
				}
			);

			$is_view_event_found                = count( $events ) > 0;
			$response['view_event']['is_found'] = $is_view_event_found;
			$response['limit_time_max']         = $time_limit_adjusted;
			if ( $is_view_event_found ) {
				$view_event                       = current( $events );
				$time_passed                      = ( new DateTime( 'now' ) )->getTimestamp() - $view_event->created_at->getTimestamp();
				$time_remaining_error_margin      = ScoreCalculator::VIEW_EVENT_ERROR_MARGIN_SECONDS / 2; // To account for network delays and such.
				$time_remaining                   = $time_limit_adjusted - $time_passed + $time_remaining_error_margin;
				$response['limit_time_remaining'] = max( 0, $time_remaining ); // No point in returing negative values.
				$response['config']               = $question->get_public_properties();
			} else {
				$response['config'] = null;
			}
		} else {
			$response['config'] = $question->get_public_properties();
		}

		if ( isset( $response['config']['text'] ) ) {
			// Parse Markdown texts into HTML.
			$response['config']['text'] = Template::string( $response['config']['text'] )->render( array(), true );
		}

		return $response;
	}
}
