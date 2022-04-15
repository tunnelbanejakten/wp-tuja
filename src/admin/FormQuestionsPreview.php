<?php
namespace tuja\admin;

use tuja\data\model\Group;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\util\Template;

class FormQuestionsPreview {

	public function __construct() {
		$qg_dao                = new QuestionGroupDao();
		$this->question_groups = array();
		if ( isset( $_GET['tuja_question_group'] ) ) {
			$question_group_id     = intval( $_GET['tuja_question_group'] );
			$this->question_groups = array( $qg_dao->get( $question_group_id ) );
		} elseif ( isset( $_GET['tuja_form'] ) ) {
			$form_id               = intval( $_GET['tuja_form'] );
			$this->question_groups = $qg_dao->get_all_in_form( $form_id );
		}
	}

	public function output() {
		if ( empty( $this->question_groups ) ) {
			print 'No questions available';

			return;
		}

		$dao = new QuestionDao();
		array_walk(
			$this->question_groups,
			function ( QuestionGroup $question_group ) use ( $dao ) {

				$question_group_name = $question_group->text;
				printf( '<h3>%s</h3>', $question_group_name );

				$question_group_description = isset( $question_group->text_description ) ? Template::string( $question_group->text_description )->render( array(), true ) : null;
				if ( isset( $question_group_description ) ) {
					printf( '<div>%s</div>', $question_group_description );
				}

				$questions = $dao->get_all_in_group( $question_group->id );
				array_walk(
					$questions,
					function ( AbstractQuestion $question ) {

						$field_name    = uniqid();
						$is_read_only  = false;
						$answer_object = null;
						$group         = Group::sample();

						$html_field = $question->get_html(
							$field_name,
							$is_read_only,
							$answer_object,
							$group
						);

						printf( '<div class="tuja-question">%s</div>', $html_field );
					}
				);
			}
		);

	}
}
