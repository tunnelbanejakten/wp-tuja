<?php


namespace tuja\frontend;


use tuja\data\model\question\AbstractQuestion;
use tuja\util\concurrency\LockValuesList;

class FormUserChanges {
	private $values;

	public function __construct() {
		$this->values = new LockValuesList();
	}

	/**
	 * @param $tracked_answers_string
	 * @param $all_answer_objects
	 *
	 * @return array
	 */
	public static function get_updated_answer_objects( string $tracked_answers_string, array $all_answer_objects ) {
		$new_checksums = new LockValuesList();
		foreach ( $all_answer_objects as $question_id => $answer_object ) {
			$checksum_after = self::calculate_checksum( $answer_object );
			$new_checksums->add_value( $question_id, $checksum_after );
		}

		$ref_checksums = LockValuesList::from_string( $tracked_answers_string );

		$updated_question_ids = $ref_checksums->get_invalid_ids( $new_checksums );

		return array_combine(
			$updated_question_ids,
			array_map( function ( $id ) use ( $all_answer_objects ) {
				return $all_answer_objects[ $id ];
			}, $updated_question_ids ) );
	}

	public function track_answer( AbstractQuestion $question, $answer_object ) {
		$value_checksum = self::calculate_checksum( $answer_object );
		$this->values->add_value( $question->id, $value_checksum );
	}

	private static function calculate_checksum( $answer_object ): int {
		return crc32( json_encode( $answer_object ) );
	}

	public function get_tracked_answers_string(): string {
		return $this->values->to_string();
	}
}