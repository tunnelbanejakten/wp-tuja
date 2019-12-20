<?php

include_once '../src/data/model/question/AbstractQuestion.php';
include_once '../src/data/model/question/OptionsQuestion.php';
include_once '../src/util/score/AutoScoreResult.php';

use tuja\data\model\question\OptionsQuestion;

function assert_score( $question, $answer, $expected_score, $expected_confidence = null ) {
	$actual = $question->score( $answer );
	assert( $actual->score == $expected_score );
	if ( isset( $expected_confidence ) ) {
		$confidence_check = $expected_confidence - 0.1 <= $actual->confidence && $actual->confidence <= $expected_confidence + 0.1;
		if ( ! $confidence_check ) {
			printf( '💥 Got %f but expected %f (±10%%) for input %s', $actual->confidence, $expected_confidence, join( ', ', $answer ) );
		}
		assert( $confidence_check );
	}
}

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

assert_score( $question, [ 'alice' ], 10, 1.0 );
assert_score( $question, [ 'ALICE' ], 10, 1.0 ); // case insensitive
assert_score( $question, [ 'bob' ], 10, 1.0 );
assert_score( $question, [ 'carol' ], 0, 1.0 );
assert_score( $question, [ 'alice', 'bob' ], 0, 1.0 ); // only one answer is allowed
assert_score( $question, [ 'alicia' ], 0, 1.0 ); // alicia is not an exact match
assert_score( $question, [ 'bobb' ], 0, 1.0 ); // bobb is not an exact match
assert_score( $question, [ 'carol' ], 0, 1.0 );
assert_score( $question, [ 'trudy' ], 0, 1.0 );


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

assert_score( $question, [ 'alice' ], 0, 1.0 );
assert_score( $question, [ 'bob' ], 0, 1.0 );
assert_score( $question, [ 'carol' ], 0, 1.0 );
assert_score( $question, [ 'alice', 'bob' ], 10, 1.0 );
assert_score( $question, [ 'alice', 'bobb' ], 0, 1.0 ); // bobb is not an exact match
assert_score( $question, [ 'ALICE', 'BOB' ], 10, 1.0 ); // case insensitive
assert_score( $question, [ 'alice', 'bob', 'carol' ], 0, 1.0 ); // one incorrect choice -- all wrong

