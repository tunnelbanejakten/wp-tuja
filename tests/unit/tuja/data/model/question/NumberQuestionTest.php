<?php

namespace data\model\question;

use tuja\data\model\question\NumberQuestion;

class NumberQuestionTest extends AbstractQuestionTest {

	/**
	 * @test
	 * @dataProvider score_data
	 */
	public function score( $answer, $expected_score, $expected_confidence ) {
		$question = new NumberQuestion(
			null,
			null,
			null,
			0,
			0,
			0,
			10,
			100,
			null);

		$this->assert_score( $question, $answer, $expected_score, $expected_confidence );
	}

	public function score_data() {
		return [
			[ 100, 10, 1.0 ],
			[ '100', 10, 1.0 ],
			[ [ 100 ], 10, 1.0 ],
			[ [ '100' ], 10, 1.0 ],
			[ 111, 0, 1.0 ],
			[ 109, 0, 0.9 ],
			[ 105, 0, 0.5 ],
			[ 101, 0, 0.1 ],
			[ 99, 0, 0.1 ],
			[ 95, 0, 0.5 ],
			[ 91, 0, 0.9 ],
			[ 90, 0, 1.0 ],
			[ 89, 0, 1.0 ],
			[ 0, 0, 1.0 ],
			[ null, 0, 1.0 ],
			[ '', 0, 1.0 ],
			[ '95', 0, 0.5 ],
			[ [ 95 ], 0, 0.5 ],
			[ [ 0 ], 0, 1.0 ],
			[ [ null ], 0, 1.0 ],
			[ [ '' ], 0, 1.0 ],
			[ [ 0, 100 ], 0, 1.0 ],
			[ [ '95' ], 0, 0.5 ],
		];
	}


}
