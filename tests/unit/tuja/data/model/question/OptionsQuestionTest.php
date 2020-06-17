<?php

namespace data\model\question;

use tuja\data\model\question\NumberQuestion;
use tuja\data\model\question\OptionsQuestion;
use tuja\data\model\question\TextQuestion;

class OptionsQuestionTest extends AbstractQuestionTest {

	/**
	 * @test
	 * @dataProvider grading_one_of_data
	 */
	public function grading_one_of( $answer, $expected_score, $expected_confidence ) {
		$question = new OptionsQuestion(
			null,
			null,
			0,
			0,
			10,
			10,
			OptionsQuestion::GRADING_TYPE_ONE_OF,
			true,
			[ 'alice', 'bob' ],
			[ 'alice', 'bob', 'carol', 'dave', 'emily' ],
			0 );

		$this->assert_score( $question, $answer, $expected_score, $expected_confidence );
	}

	public function grading_one_of_data() {
		return [
			[ [ 'alice' ], 10, 1.0 ],
			// case insensitive
			[ [ 'ALICE' ], 10, 1.0 ],
			[ [ 'bob' ], 10, 1.0 ],
			[ [ 'carol' ], 0, 1.0 ],
			// only one answer is allowed
			[ [ 'alice', 'bob' ], 0, 1.0 ],
			// alicia is not an exact match
			[ [ 'alicia' ], 0, 1.0 ],
			// bobb is not an exact match
			[ [ 'bobb' ], 0, 1.0 ],
			[ [ 'carol' ], 0, 1.0 ],
			[ [ 'trudy' ], 0, 1.0 ],
		];
	}

	/**
	 * @test
	 * @dataProvider grading_all_of_data
	 */
	public function grading_all_of( $answer, $expected_score, $expected_confidence ) {
		$question = new OptionsQuestion(
			null,
			null,
			0,
			0,
			10,
			10,
			OptionsQuestion::GRADING_TYPE_ALL_OF,
			false,
			[ 'alice', 'bob' ],
			[ 'alice', 'bob', 'carol', 'dave', 'emily' ],
			0 );

		$this->assert_score( $question, $answer, $expected_score, $expected_confidence );
	}

	public function grading_all_of_data() {
		return [
			[ [ 'alice' ], 0, 1.0 ],
			[ [ 'bob' ], 0, 1.0 ],
			[ [ 'carol' ], 0, 1.0 ],
			[ [ 'alice', 'bob' ], 10, 1.0 ],
			[ [ 'alice', 'bobb' ], 0, 1.0 ],
			[ [ 'ALICE', 'BOB' ], 10, 1.0 ],
			[ [ 'alice', 'bob', 'carol' ], 0, 1.0 ],
		];
	}


}
