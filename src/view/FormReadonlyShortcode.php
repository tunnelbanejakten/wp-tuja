<?php

namespace tuja\view;

use tuja\data\model\question\AbstractQuestion;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;

class FormReadonlyShortcode
{
	private $question_dao;
	private $group_dao;
	private $response_dao;

	public function __construct( $wpdb, $form_id, $group_key ) {
		$this->form_id      = $form_id;
		$this->group_key    = $group_key;
		$this->question_dao = new QuestionDao();
		$this->group_dao    = new GroupDao();
		$this->response_dao = new ResponseDao();
	}

	public function render(): String {
		$group_key = $this->group_key;
		$group     = $this->group_dao->get_by_key( $group_key );
		if ( $group === false ) {
			return sprintf( '<p class="tuja-message tuja-message-error">%s</p>', 'Oj då, vi vet inte vilket lag du tillhör.' );
		}

		$responses = $this->response_dao->get_latest_by_group( $group->id );
		$questions = $this->question_dao->get_all_in_form( $this->form_id );

		return join( array_map( function ( AbstractQuestion $question ) use ( $responses ) {
			$response = $responses[ $question->id ];

			return sprintf( '<div class="tuja-question"><p><strong>%s</strong>%s</p></div>',
				$question->text,
				isset( $response ) ? '<br>' . join( '<br>', $response->submitted_answer ) : '' );
		}, $questions ) );
	}

}