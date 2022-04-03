<?php

namespace data\model\question;

use tuja\data\model\question\TextQuestion;

class TextQuestionTest extends AbstractQuestionTest {

	/**
	 * @test
	 * @dataProvider grade_type_one_of_data
	 */
	public function grade_type_one_of( $answer, $expected_score, $expected_confidence ) {
		$question = new TextQuestion(
			null,
			'Testing one-of grading type',
			null,
			0,
			0,
			0,
			10,
			TextQuestion::GRADING_TYPE_ONE_OF,
			true,
			[ 'alice', 'bob' ] );

		$this->assert_score( $question, $answer, $expected_score, $expected_confidence );
	}

	public function grade_type_one_of_data() {
		return [
			[ [ 'alice' ], 10, 1.0 ],
			[ [ 'bob' ], 10, 1.0 ],
			[ [ 'alice', 'bob' ], 10, 1.0 ],
			[ [ 'ALICE', 'BOB' ], 10, 1.0 ],
			// alicia okay but bobb is not:
			[ [ 'alicia', 'bobb' ], 10, 0.6 ],
			[ [ 'alice', 'bob', 'carol' ], 10, 1.0 ],
			[ [ 'alice', 'carol' ], 10, 0.9 ],
			[ [ 'nathan', 'victor' ], 0, 0.8 ],
			[ [ 'carol' ], 0, 0.8 ],
			[ [ 'trudy' ], 0, 1.0 ],
		];
	}

	/**
	 * @test
	 * @dataProvider grade_type_one_of_with_invalid_answers_data
	 */
	public function grade_type_one_of_with_invalid_answers( $answer, $expected_score, $expected_confidence ) {
		$question = new TextQuestion(
			null,
			'Boat or ship?',
			null,
			0,
			0,
			0,
			10,
			TextQuestion::GRADING_TYPE_ONE_OF,
			true,
			[ 'boat', 'ship' ],
			[ 'ball', 'sharp' ] );

		$this->assert_score( $question, $answer, $expected_score, $expected_confidence );
	}

	public function grade_type_one_of_with_invalid_answers_data() {
		return [
			[ [ 'boat' ], 10, 1.0 ],
			[ [ 'booat' ], 10, 0.8 ],
			[ [ 'bloat' ], 10, 0.8 ],
			[ [ 'ball' ], 0, 1.0 ],
			[ [ 'bold' ], 0, 0.6 ],
			[ [ 'ship' ], 10, 1.0 ],
			[ [ 'shop' ], 0, 0.3 ],
			[ [ 'shape' ], 0, 0.8 ],
			[ [ 'sharp' ], 0, 1.0 ],
		];
	}

	/**
	 * @test
	 * @dataProvider grade_type_ordered_percent_of_data
	 */
	public function grade_type_ordered_percent_of( $answer, $expected_score, $expected_confidence ) {
		$question = new TextQuestion(
			null,
			'Testing ordered-precent-of grading type',
			null,
			0,
			0,
			0,
			10,
			TextQuestion::GRADING_TYPE_ORDERED_PERCENT_OF,
			true,
			[ 'stockholm', 'copenhagen', 'oslo', 'helsinki' ] ); // What is the capital of {possible_answer}?

		$this->assert_score( $question, $answer, $expected_score, $expected_confidence );
	}

	public function grade_type_ordered_percent_of_data() {
		return [
			[ [ 'STOCKHOLM', '', 'Oslo' ], 5, 1.0 ],
			[ [ 'stokholm', '', 'Oslo' ], 5, 0.9 ],
			// 7.5 rounded up:
			[ [ 'stokholm', '', 'Oslo', 'HELSINKI' ], 8, 0.9 ],
			// 2.5 rounded up:
			[ [ '', 'copenhagen', '', '' ], 3, 0.9 ],
			[ [ 'stockholm', 'copenhagen', 'oslo', 'helsinki' ], 10, 1.0 ],
			// Slightly misspelled:
			[ [ 'Stockholm', 'copenhaven', 'Oslo', 'helsinky' ], 10, 0.9 ],
			// Wrong order. All wrong:
			[ [ 'helsinki', 'stockholm', 'copenhagen', 'oslo' ], 0, 0.9 ],
			[ [ '', '', '', '' ], 0, 1.0 ],
		];
	}

	/**
	 * @test
	 * @dataProvider grade_type_unordered_percent_of_data
	 */
	public function grade_type_unordered_percent_of( $answer, $expected_score, $expected_confidence ) {
		$question = new TextQuestion(
			null,
			'Testing unordered-percent-of grading type',
			null,
			0,
			0,
			0,
			10,
			TextQuestion::GRADING_TYPE_UNORDERED_PERCENT_OF,
			true,
			[
				'stockholm',
				'copenhagen',
				'oslo',
				'helsinki',
				'reykjavik'
			] ); // Which are the capitals of the nordic countries?

		$this->assert_score( $question, $answer, $expected_score, $expected_confidence );
	}

	public function grade_type_unordered_percent_of_data() {
		return [
			[ [ 'STOCKHOLM', 'Oslo' ], 4, 1.0 ],
			[ [ 'stokholm', 'Oslo' ], 4, 0.9 ],
			[ [ 'stokholm', 'Oslo', 'HELSINKI' ], 6, 0.9 ],
			[ [ 'copenhagen' ], 2, 1.0 ],
			[ [ 'stockholm', 'copenhagen', 'oslo', 'helsinki', 'reykjavik' ], 10, 1.0 ],
			// Slightly misspelled:
			[ [ 'Stockholm', 'copenhaven', 'Oslo', 'helsinky', 'reikjavik' ], 10, 0.9 ],
			// Different order:
			[ [ 'helsinki', 'reykjavik', 'stockholm', 'copenhagen', 'oslo' ], 10, 1.0 ],
			// Incorrect answers are ignored:
			[ [ 'Stockholm', 'copenhaven', 'Oslo', 'helsinky', 'berlin' ], 8, 0.9 ],
		];
	}

	/**
	 * @test
	 * @dataProvider grade_type_all_of_data
	 */
	public function grade_type_all_of( $answer, $expected_score, $expected_confidence ) {
		$question = new TextQuestion(
			null,
			'Testing all-of grading type',
			null,
			0,
			0,
			0,
			10,
			TextQuestion::GRADING_TYPE_ALL_OF,
			true,
			[ 'alice', 'bob' ] );

		$this->assert_score( $question, $answer, $expected_score, $expected_confidence );
	}

	public function grade_type_all_of_data() {
		return [
			// full points:
			[ [ 'alice', 'bob' ], 10, 1.0 ],
			// full points:
			[ [ 'ALICE', 'BOB' ], 10, 1.0 ],
			// both options are pretty close so confidence for a score of 0 is not high:
			[ [ 'alicia', 'bobb' ], 0, 0.5 ],
			// one answer is pretty far off so we confidence for a score of 0 is pretty high:
			[ [ 'alice', 'carol' ], 0, 0.9 ],
			// Incorrect number of answers => automatic fail:
			[ [ 'alice' ], 0, 1.0 ],
			// Incorrect number of answers => automatic fail:
			[ [ 'bob' ], 0, 1.0 ],
			// Incorrect number of answers => automatic fail:
			[ [ 'alice', 'bob', 'carol' ], 0, 1.0 ],
			// Incorrect number of answers => automatic fail:
			[ [ 'carol' ], 0, 1.0 ],
			// Incorrect number of answers => automatic fail:
			[ [ 'trudy' ], 0, 1.0 ],
		];
	}
}
