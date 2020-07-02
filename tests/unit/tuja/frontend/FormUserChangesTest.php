<?php

namespace frontend;

use tuja\data\model\question\TextQuestion;
use tuja\frontend\FormUserChanges;
use PHPUnit\Framework\TestCase;

class FormUserChangesTest extends TestCase {

	/**
	 * @test
	 * @dataProvider get_updated_answer_objects_data
	 */
	public function get_updated_answer_objects( $old_values, $new_values, $expected_values ) {
		// arrange

		$user_changes = new FormUserChanges( 1 );

		foreach ( $old_values as $question_id => $answer_object ) {
			$user_changes->track_answer( new TextQuestion( 'question', null, $question_id ), $answer_object );
		}

		// act
		$actual = $user_changes->get_updated_answer_objects(
			$user_changes->get_tracked_answers_string(),
			$new_values );

		// assert
		self::assertEquals( $expected_values, $actual );
	}

	public function get_updated_answer_objects_data(): array {
		return [
			[
				// Ignore identical answers
				[ '10' => [ 'answer' ] ],
				[ '10' => [ 'answer' ] ],
				[]
			],
			[
				// Keep changed answers, ignore identical answers
				[
					'10' => [ 'old answer' ],
					'11' => [ 'same answer' ]
				],
				[
					'10' => [ 'updated answer' ],
					'11' => [ 'same answer' ]
				],
				[ '10' => [ 'updated answer' ] ]
			],
			[
				// Disregard answers which were not present before (question 12 here)
				[
					'10' => [ 'old answer' ],
					'11' => [ 'same answer' ]
				],
				[
					'10' => [ 'updated answer' ],
					'11' => [ 'same answer' ],
					'12' => [ 'answer from another user in same team' ]
				],
				[ '10' => [ 'updated answer' ] ]
			],
			[
				// Array with numbers, one change
				[ '10' => [ 42 ] ],
				[ '10' => [ 420 ] ],
				[ '10' => [ 420 ] ]
			],
			[
				// Array with numbers, no change
				[ '10' => [ 42 ] ],
				[ '10' => [ 42 ] ],
				[]
			],
			[
				//
				[
					'10' => 42,
					'11' => [ 'key1' => 'value', 'key2' => 'value' ],
					'12' => [ 'friends' => [ 'alice', 'bob' ], 'foes' => [ 'trudy' ] ],
					'13' => [ 'favourite_foods' => [ 'hot dog' ] ]
				],
				[
					'10' => 420, // updated (simple value)
					'11' => [ 'key1' => 'value', 'key2' => 'updated value' ], // updated (keys and values)
					'12' => [ 'friends' => [ 'alice', 'bob' ], 'foes' => [ 'trudy' ] ], // some things never change
					'13' => [ 'favourite_foods' => [ 'hamburger', 'chorizo' ] ] // updated (array content)
				],
				[
					'10' => 420,
					'11' => [ 'key1' => 'value', 'key2' => 'updated value' ],
					'13' => [ 'favourite_foods' => [ 'hamburger', 'chorizo' ] ]
				]
			]
		];
	}

}
