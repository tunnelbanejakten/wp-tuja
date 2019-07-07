<?php

include_once '../src/data/model/question/AbstractQuestion.php';
include_once '../src/data/model/question/NumberQuestion.php';
include_once '../src/util/score/AutoScoreResult.php';

use \tuja\data\model\question\NumberQuestion;

function assert_score( $question, $answer, $expected_score, $expected_confidence = null ) {
	$actual = $question->score( $answer );
	assert( $actual->score == $expected_score );
	if ( isset( $expected_confidence ) ) {
		$confidence_check = $expected_confidence - 0.1 <= $actual->confidence && $actual->confidence <= $expected_confidence + 0.1;
		if ( ! $confidence_check ) {
			printf( '💥 Got %f but expected %f (±10%%) for input %s', $actual->confidence, $expected_confidence, json_encode($answer) );
		}
		assert( $confidence_check );
	}
}

$question = new NumberQuestion(
	null,
	null,
	0,
	0,
	0,
	10,
	100);

assert_score( $question, 100, 10, 1.0 );
assert_score( $question, '100', 10, 1.0 );
assert_score( $question, [ 100 ], 10, 1.0 );
assert_score( $question, [ '100' ], 10, 1.0 );

assert_score( $question, 111, 0, 1.0 );
assert_score( $question, 109, 0, 0.9 );
assert_score( $question, 105, 0, 0.5 );
assert_score( $question, 101, 0, 0.1 );
assert_score( $question, 99, 0, 0.1 );
assert_score( $question, 95, 0, 0.5 );
assert_score( $question, 91, 0, 0.9 );
assert_score( $question, 90, 0, 1.0 );
assert_score( $question, 89, 0, 1.0 );

assert_score( $question, 0, 0, 1.0 );
assert_score( $question, null, 0, 1.0 );
assert_score( $question, '', 0, 1.0 );
assert_score( $question, '95', 0, 0.5 );
assert_score( $question, [ 95 ], 0, 0.5 );
assert_score( $question, [ 0 ], 0, 1.0 );
assert_score( $question, [ null ], 0, 1.0 );
assert_score( $question, [ '' ], 0, 1.0 );
assert_score( $question, [ 0, 100 ], 0, 1.0 );
assert_score( $question, [ '95' ], 0, 0.5 );
