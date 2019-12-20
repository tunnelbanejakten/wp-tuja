<?php

include_once '../src/data/model/question/AbstractQuestion.php';
include_once '../src/data/model/question/TextQuestion.php';
include_once '../src/util/score/AutoScoreResult.php';

use tuja\data\model\question\TextQuestion;

function assert_score( $question, $answer, $expected_score, $expected_confidence = null ) {
	$actual = $question->score( $answer );
	if ( $actual->score !== $expected_score ) {
		printf( "%s: 💥 Got score %f but expected %f for input %s\n", $question->text, $actual->score, $expected_score, join( ', ', $answer ) );
	}
	if ( isset( $expected_confidence ) ) {
		$confidence_check = $expected_confidence - 0.1 <= $actual->confidence && $actual->confidence <= $expected_confidence + 0.1;
		if ( ! $confidence_check ) {
			printf( "%s: 💥 Got %f but expected %f (±10%%) for input %s\n", $question->text, $actual->confidence, $expected_confidence, join( ', ', $answer ) );
		}
	}
}

$question = new TextQuestion(
	'Testing one-of grading type',
	null,
	0,
	0,
	0,
	10,
	TextQuestion::GRADING_TYPE_ONE_OF,
	true,
	[ 'alice', 'bob' ] );

assert_score( $question, [ 'alice' ], 10, 1.0 );
assert_score( $question, [ 'bob' ], 10, 1.0 );
assert_score( $question, [ 'alice', 'bob' ], 10, 1.0 );
assert_score( $question, [ 'ALICE', 'BOB' ], 10, 1.0 );
assert_score( $question, [ 'alicia', 'bobb' ], 10, 0.6 ); // alicia okay but bobb is not
assert_score( $question, [ 'alice', 'bob', 'carol' ], 10, 1.0 );
assert_score( $question, [ 'alice', 'carol' ], 10, 0.9 );
assert_score( $question, [ 'nathan', 'victor' ], 0, 0.8 );
assert_score( $question, [ 'carol' ], 0, 0.8 );
assert_score( $question, [ 'trudy' ], 0, 1.0 );

$question = new TextQuestion(
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

assert_score( $question, [ 'boat' ], 10, 1.0 );
assert_score( $question, [ 'booat' ], 10, 0.8 );
assert_score( $question, [ 'bloat' ], 10, 0.8 );
assert_score( $question, [ 'ball' ], 0, 1.0 );
assert_score( $question, [ 'bold' ], 0, 0.6 );
assert_score( $question, [ 'ship' ], 10, 1.0 );
assert_score( $question, [ 'shop' ], 0, 0.3 );
assert_score( $question, [ 'shape' ], 0, 0.8 );
assert_score( $question, [ 'sharp' ], 0, 1.0 );

$question = new TextQuestion(
	'Testing ordered-precent-of grading type',
	null,
	0,
	0,
	0,
	10,
	TextQuestion::GRADING_TYPE_ORDERED_PERCENT_OF,
	true,
	[ 'stockholm', 'copenhagen', 'oslo', 'helsinki' ] ); // What is the capital of {possible_answer}?

assert_score( $question, [ 'STOCKHOLM', '', 'Oslo' ], 5, 1.0 );
assert_score( $question, [ 'stokholm', '', 'Oslo' ], 5, 0.9 );
assert_score( $question, [ 'stokholm', '', 'Oslo', 'HELSINKI' ], 8, 0.9 ); // 7.5 rounded up.
assert_score( $question, [ '', 'copenhagen', '', '' ], 3 ); // 2.5 rounded up.
assert_score( $question, [ 'stockholm', 'copenhagen', 'oslo', 'helsinki' ], 10, 1.0 );
assert_score( $question, [ 'Stockholm', 'copenhaven', 'Oslo', 'helsinky' ], 10, 0.9 ); // Slightly misspelled.
assert_score( $question, [ 'helsinki', 'stockholm', 'copenhagen', 'oslo' ], 0, 0.9 ); // Wrong order. All wrong.
assert_score( $question, [ '', '', '', '' ], 0, 1.0 );

$question = new TextQuestion(
	'Testing unordered-percent-of grading type',
	null,
	0,
	0,
	0,
	10,
	TextQuestion::GRADING_TYPE_UNORDERED_PERCENT_OF,
	true,
	[ 'stockholm', 'copenhagen', 'oslo', 'helsinki', 'reykjavik' ] ); // Which are the capitals of the nordic countries?

assert_score( $question, [ 'STOCKHOLM', 'Oslo' ], 4, 1.0 );
assert_score( $question, [ 'stokholm', 'Oslo' ], 4, 0.9 );
assert_score( $question, [ 'stokholm', 'Oslo', 'HELSINKI' ], 6, 0.9 );
assert_score( $question, [ 'copenhagen' ], 2, 1.0 );
assert_score( $question, [ 'stockholm', 'copenhagen', 'oslo', 'helsinki', 'reykjavik' ], 10, 1.0 );
assert_score( $question, [ 'Stockholm', 'copenhaven', 'Oslo', 'helsinky', 'reikjavik' ], 10, 0.9 ); // Slightly misspelled.
assert_score( $question, [ 'helsinki', 'reykjavik', 'stockholm', 'copenhagen', 'oslo' ], 10, 1.0 ); // Different order.
assert_score( $question, [ 'Stockholm', 'copenhaven', 'Oslo', 'helsinky', 'berlin' ], 8, 0.9 ); // Incorrect answers are ignored.

$question = new TextQuestion(
	'Testing all-of grading type',
	null,
	0,
	0,
	0,
	10,
	TextQuestion::GRADING_TYPE_ALL_OF,
	true,
	[ 'alice', 'bob' ] );

assert_score( $question, [ 'alice', 'bob' ], 10, 1.0 ); // full points
assert_score( $question, [ 'ALICE', 'BOB' ], 10, 1.0 ); // full points
assert_score( $question, [ 'alicia', 'bobb' ], 0, 0.5 ); // both options are pretty close so confidence for a score of 0 is not high
assert_score( $question, [ 'alice', 'carol' ], 0, 0.9 ); // one answer is pretty far off so we confidence for a score of 0 is pretty high

// Incorrect number of answers => automatic fail:
assert_score( $question, [ 'alice' ], 0, 1.0 );
assert_score( $question, [ 'bob' ], 0, 1.0 );
assert_score( $question, [ 'alice', 'bob', 'carol' ], 0, 1.0 );
assert_score( $question, [ 'carol' ], 0, 1.0 );
assert_score( $question, [ 'trudy' ], 0, 1.0 );
