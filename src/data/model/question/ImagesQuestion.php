<?php

namespace tuja\data\model\question;


use tuja\admin\AdminUtils;
use tuja\data\model\Group;
use tuja\util\score\AutoScoreResult;
use tuja\view\FieldImages;

class ImagesQuestion extends AbstractQuestion {

	/**
	 * Grades an answer and returns the score for the answer.
	 */
	function score( $answer_object ) : AutoScoreResult {
		return new AutoScoreResult(0, 1.0);
	}

	/**
	 * Returns the HTML used to render this question.
	 */
	function get_html( $field_name, $is_read_only, $answer_object, Group $group = null ) {
		return $this->create_field($is_read_only)->render( $field_name, $answer_object, $group );
	}

	/**
	 * Gathers data from $_POST about the current question. The response from this function
	 * can be sent to score(...) and can be stored in the database.
	 */
	function get_answer_object( $field_name ) {
		return $this->create_field()->get_posted_answer( $field_name );
	}

	private function create_field($is_read_only = false): FieldImages {
		$field = new FieldImages( $this->text, $this->text_hint, $is_read_only );

		return $field;
	}

	function get_correct_answer_html() {
		return null;
	}

	function get_submitted_answer_html( $answer_object, Group $group ) {
		return AdminUtils::get_image_thumbnails_html( $answer_object, $group->random_id );
	}
}