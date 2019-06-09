<?php

namespace tuja\data\model;


use tuja\util\ReflectionUtils;

class QuestionGroup {
	public $id;
	public $random_id;
	public $form_id;

	/**
	 * @tuja-gui-editable
	 */
	public $text = '';

	/**
	 * @tuja-gui-editable
	 */
	public $sort_order = '';

	/**
	 * @tuja-gui-editable
	 */
	public $score_max = 0;

	public function validate() {
		if ( strlen( $this->text ) > 65000 ) {
			throw new ValidationException( 'text', 'Frågegruppens text är för lång.' );
		}
		if ( isset( $this->score_max ) && $this->score_max < 0 ) {
			throw new ValidationException( 'score_max', 'Maximal poäng måste vara mer än 0.' );
		}
	}

	public function get_editable_fields() {
		return ReflectionUtils::get_editable_properties( new QuestionGroup() );
	}

}