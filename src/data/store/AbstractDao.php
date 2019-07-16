<?php

namespace tuja\data\store;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use tuja\data\model\Form;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\question\ImagesQuestion;
use tuja\data\model\question\NumberQuestion;
use tuja\data\model\question\OptionsQuestion;
use tuja\data\model\question\TextQuestion;
use tuja\util\Id;

class AbstractDao {
	const QUESTION_TYPE_TEXT = 'text';
	const QUESTION_TYPE_NUMBER = 'number';
	const QUESTION_TYPE_PICK_ONE = 'pick_one';
	const QUESTION_TYPE_PICK_MULTI = 'pick_multi';
	const QUESTION_TYPE_IMAGES = 'images';
	const QUESTION_TYPE_TEXT_MULTI = 'text_multi';

	protected $id;
	protected $wpdb;
	protected $table;

	function __construct() {
		global $wpdb;
		$this->id   = new Id();
		$this->wpdb = $wpdb;
	}

	protected function get_object( $mapper, $query, ...$arguments ) {
		$db_results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $arguments ), OBJECT );
		if ( $db_results !== false && count( $db_results ) > 0 ) {
			return $mapper( $db_results[0] );
		}

		return false;
	}

	protected function get_objects( $mapper, $query, ...$arguments ) {
		$db_results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $arguments ), OBJECT );
		$results    = [];
		foreach ( $db_results as $result ) {
			$results[] = $mapper( $result );
		}

		return $results;
	}

	public static function to_db_date( DateTime $dateTime = null ) {
		if ( $dateTime != null ) {
			return $dateTime->getTimestamp(); // Unix timestamps are always UTC
		} else {
			return null;
		}
	}

	public static function from_db_date( $dbDate ) {
		if ( ! empty( $dbDate ) ) {
			return new DateTimeImmutable( '@' . $dbDate, new DateTimeZone( 'UTC' ) );
		} else {
			return null;
		}
	}

	protected static function to_form_question( $result ): AbstractQuestion {
		$config = json_decode( $result->answer, true );

		switch ( $result->type ) {
			case self::QUESTION_TYPE_TEXT_MULTI:
			case self::QUESTION_TYPE_TEXT:
				$q = new TextQuestion(
					$result->text,
					$result->text_hint,
					$result->id,
					$result->question_group_id,
					$result->sort_order,
					@$config['score_max'],
					@$config['score_type'],
					$result->type == self::QUESTION_TYPE_TEXT,
					@$config['values'] );

				return $q;
			case self::QUESTION_TYPE_PICK_ONE:
			case self::QUESTION_TYPE_PICK_MULTI:
				$q = new OptionsQuestion(
					$result->text,
					$result->text_hint,
					$result->id,
					$result->question_group_id,
					$result->sort_order,
					@$config['score_max'],
					@$config['score_type'],
					$result->type == self::QUESTION_TYPE_PICK_ONE,
					@$config['values'],
					@$config['options'],
					false );

				return $q;
			case self::QUESTION_TYPE_IMAGES:
				$q = new ImagesQuestion(
					$result->text,
					$result->text_hint,
					$result->id,
					$result->question_group_id,
					$result->sort_order,
					@$config['score_max'] );
				return $q;
			case self::QUESTION_TYPE_NUMBER:
				$q = new NumberQuestion(
					$result->text,
					$result->text_hint,
					$result->id,
					$result->question_group_id,
					$result->sort_order,
					@$config['score_max'],
					@$config['value'] );

				return $q;
			default:
				throw new Exception( 'Unsupported type of question: ' . $result->type );
		}
	}

	protected static function to_form($result): Form
	{
		$f = new Form();
		$f->id = $result->id;
		$f->competition_id = $result->competition_id;
		$f->name = $result->name;
		$f->allow_multiple_responses_per_group = $result->allow_multiple_responses_per_team;
		$f->submit_response_start = self::from_db_date($result->submit_response_start);
		$f->submit_response_end = self::from_db_date($result->submit_response_end);
		return $f;
	}
}