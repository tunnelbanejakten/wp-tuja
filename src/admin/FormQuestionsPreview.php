<?php
namespace tuja\admin;

use tuja\data\model\Group;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\store\QuestionDao;

class FormQuestionsPreview {

	public function __construct() {
		$dao             = new QuestionDao();
		$this->questions = $dao->get_all_in_group( intval( $_GET['tuja_question_group'] ) );
	}

	public function output() {
		if ( empty( $this->questions ) ) {
			print 'No questions available';

			return;
		}

		array_walk(
			$this->questions,
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
}
