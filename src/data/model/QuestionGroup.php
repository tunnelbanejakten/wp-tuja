<?php

namespace tuja\data\model;


use Exception;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionDao;
use tuja\util\ReflectionUtils;

class QuestionGroup {

	const QUESTION_FILTER_ALL = 'all';
	const QUESTION_FILTER_FIXED_RANDOM = 'one_random_with_even_group_distribution';

	public $id;
	public $random_id;
	public $form_id;

	public $text = '';

	public $text_description = '';

	public $sort_order = '';

	public $score_max = 0;

	public $question_filter = self::QUESTION_FILTER_ALL;

	public function validate() {
		if ( strlen( $this->text ) > 65000 ) {
			throw new ValidationException( 'text', 'Frågegruppens rubrik är för lång.' );
		}
		if ( strlen( $this->text_description ) > 65000 ) {
			throw new ValidationException( 'text', 'Frågegruppens beskrivning är för lång.' );
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

	function get_filtered_questions( QuestionDao $question_dao, GroupDao $group_dao, Group $group ) {
		$questions = $question_dao->get_all_in_group( $this->id );
		switch ( $this->question_filter ) {
			case self::QUESTION_FILTER_FIXED_RANDOM:
				// Get list of all competing teams in competition
				// Sort list by pseudo-random team property which will not change during competition, e.g. "random_id" or "md5 hash of name"
				// Split sorted list in N smaller lists of equal size (N is the number of questions in the question group). Similar to https://www.php.net/manual/en/function.array-chunk.php.

				$competing_groups = array_filter(
					$group_dao->get_all_in_competition( $group->competition_id ),
					function ( Group $group ) {
						return ! $group->get_category()->get_rules()->is_crew();
					} );

				$group_count                      = count( $competing_groups );
				$question_count                   = count( $questions );
				$desired_group_count_per_question = 1.0 * $group_count / $question_count;

				$mapper = function ( Group $grp ) {
					return $grp->random_id;
				};

				$groups_sort_values = array_map( $mapper, $competing_groups );
				$group_sort_value   = $mapper( $group );

				sort( $groups_sort_values );

				$group_index = array_search( $group_sort_value, $groups_sort_values );

				if ( $group_index === false ) {
					return $questions;
				}

				$index_start  = 0;
				$desired_stop = 0;
				for ( $i = 0; $i < $question_count; $i ++ ) {
					$desired_stop += $desired_group_count_per_question;
					$index_stop   = round( $desired_stop );

					if ( $index_start <= $group_index && $group_index < $index_stop ) {
						// Return question X from the question group where X corresponds to the index of the "small list from the previous step where the group is found".

						return [ $questions[ $i ] ];
					}

					$index_start = $index_stop;
				}
				throw new Exception( 'Could not select question for this group' );
				break;
			default:
				return $questions;
				break;
		}

	}
}