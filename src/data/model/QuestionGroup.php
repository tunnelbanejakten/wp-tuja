<?php

namespace tuja\data\model;


use tuja\util\ReflectionUtils;

class QuestionGroup {
	public $id;
	public $random_id;
	public $form_id;

	public $text = '';

	public $sort_order = '';

	public $score_max = 0;

	public function validate() {
		if ( strlen( $this->text ) > 65000 ) {
			throw new ValidationException( 'text', 'Frågegruppens text är för lång.' );
		}
		if ( isset( $this->score_max ) && $this->score_max < 0 ) {
			throw new ValidationException( 'score_max', 'Maximal poäng måste vara mer än 0.' );
		}
	}

	function json_schema() {
		$str = __DIR__ . '/QuestionGroup.schema.json';

		return file_get_contents( $str );
	}

	function get_editable_properties_json() {
		$schema = json_decode( $this->json_schema(), true );

		$editable_properties = array_keys( $schema['properties'] );

		return ReflectionUtils::to_json_string( $this, $editable_properties );
	}

	function set_properties_from_json_string( $json_string ) {
		ReflectionUtils::set_properties_from_json_string(
			$this,
			$json_string,
			$this->json_schema() );
	}
}