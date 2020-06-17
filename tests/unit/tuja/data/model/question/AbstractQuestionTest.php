<?php

namespace data\model\question;

use PHPUnit\Framework\TestCase;
use tuja\data\model\question\AbstractQuestion;

abstract class AbstractQuestionTest extends TestCase {

	public function assert_score( AbstractQuestion $question, $answer, $expected_score, $expected_confidence = null ) {
		$actual = $question->score( $answer );

		self::assertEquals(
			$expected_score,
			$actual->score,
			sprintf(
				"%s: Got score %f but expected %f for input %s",
				$question->text,
				$actual->score,
				$expected_score,
				json_encode($answer) ) );

		if ( isset( $expected_confidence ) ) {
			$confidence_check = $expected_confidence - 0.1 <= $actual->confidence && $actual->confidence <= $expected_confidence + 0.1;

			self::assertTrue(
				$confidence_check,
				sprintf(
					"%s: Got %f but expected %f (±10%%) for input %s",
					$question->text,
					$actual->confidence,
					$expected_confidence,
					json_encode($answer) ) );
		}
	}
}
