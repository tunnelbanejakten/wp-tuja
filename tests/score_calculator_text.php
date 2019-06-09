<?php

include_once '../src/data/model/question/AbstractQuestion.php';
include_once '../src/data/model/question/TextQuestion.php';

use tuja\data\model\question\TextQuestion;

$question = new TextQuestion(
	null,
	null,
	0,
	0,
	0,
	10,
	TextQuestion::GRADING_TYPE_ONE_OF,
	true,
	[ 'alice', 'bob' ] );

assert( $question->score( [ 'alice' ] ) == 10 );
assert( $question->score( [ 'bob' ] ) == 10 );
assert( $question->score( [ 'alice', 'bob' ] ) == 10 );
assert( $question->score( [ 'ALICE', 'BOB' ] ) == 10 );
assert( $question->score( [ 'alicia', 'bobb' ] ) == 10 ); // alicia okay but bobb is not
assert( $question->score( [ 'alice', 'bob', 'carol' ] ) == 10 );
assert( $question->score( [ 'alice', 'carol' ] ) == 10 );
assert( $question->score( [ 'carol' ] ) == 0 );
assert( $question->score( [ 'trudy' ] ) == 0 );

$question = new TextQuestion(
	null,
	null,
	0,
	0,
	0,
	10,
	TextQuestion::GRADING_TYPE_ORDERED_PERCENT_OF,
	true,
	[ 'stockholm', 'copenhagen', 'oslo', 'helsinki' ] ); // What is the capital of {possible_answer}?

assert( $question->score( [ 'STOCKHOLM', '', 'Oslo' ] ) == 5 );
assert( $question->score( [ 'stokholm', '', 'Oslo' ] ) == 5 );
assert( $question->score( [ 'stokholm', '', 'Oslo', 'HELSINKI' ] ) == 8 ); // 7.5 rounded up.
assert( $question->score( [ '', 'copenhagen', '', '' ] ) == 3 ); // 2.5 rounded up.
assert( $question->score( [ 'stockholm', 'copenhagen', 'oslo', 'helsinki' ] ) == 10 );
assert( $question->score( [ 'Stockholm', 'copenhaven', 'Oslo', 'helsinky' ] ) == 10 ); // Slightly misspelled.
assert( $question->score( [ 'helsinki', 'stockholm', 'copenhagen', 'oslo' ] ) == 0 ); // Wrong order. All wrong.
assert( $question->score( [ '', '', '', '' ] ) == 0 );

$question = new TextQuestion(
	null,
	null,
	0,
	0,
	0,
	10,
	TextQuestion::GRADING_TYPE_UNORDERED_PERCENT_OF,
	true,
	[ 'stockholm', 'copenhagen', 'oslo', 'helsinki', 'reykjavik' ] ); // Which are the capitals of the nordic countries?

assert( $question->score( [ 'STOCKHOLM', 'Oslo' ] ) == 4 );
assert( $question->score( [ 'stokholm', 'Oslo' ] ) == 4 );
assert( $question->score( [ 'stokholm', 'Oslo', 'HELSINKI' ] ) == 6 );
assert( $question->score( [ 'copenhagen' ] ) == 2 );
assert( $question->score( [ 'stockholm', 'copenhagen', 'oslo', 'helsinki', 'reykjavik' ] ) == 10 );
assert( $question->score( [
		'Stockholm',
		'copenhaven',
		'Oslo',
		'helsinky',
		'reikjavik'
	] ) == 10 ); // Slightly misspelled.
assert( $question->score( [ 'helsinki', 'reykjavik', 'stockholm', 'copenhagen', 'oslo' ] ) == 10 ); // Different order.
assert( $question->score( [
		'Stockholm',
		'copenhaven',
		'Oslo',
		'helsinky',
		'berlin'
	] ) == 8 ); // Incorrect answers are ignored.

$question = new TextQuestion(
	null,
	null,
	0,
	0,
	0,
	10,
	TextQuestion::GRADING_TYPE_ALL_OF,
	true,
	[ 'alice', 'bob' ] );

assert( $question->score( [ 'alice' ] ) == 0 );
assert( $question->score( [ 'bob' ] ) == 0 );
assert( $question->score( [ 'alice', 'bob' ] ) == 10 );
assert( $question->score( [ 'ALICE', 'BOB' ] ) == 10 );
assert( $question->score( [ 'alicia', 'bobb' ] ) == 0 ); // alicia okay but bobb is not
assert( $question->score( [ 'alice', 'bob', 'carol' ] ) == 0 );
assert( $question->score( [ 'alice', 'carol' ] ) == 0 );
assert( $question->score( [ 'carol' ] ) == 0 );
assert( $question->score( [ 'trudy' ] ) == 0 );
