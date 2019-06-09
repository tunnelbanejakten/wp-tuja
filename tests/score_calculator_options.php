<?php

include_once '../src/data/model/question/AbstractQuestion.php';
include_once '../src/data/model/question/OptionsQuestion.php';

use tuja\data\model\question\OptionsQuestion;

$question = new OptionsQuestion(
	null,
	null,
	[ 'alice', 'bob', 'carol', 'dave', 'emily' ],
	true,
	0,
	0,
	0,
	10,
	[ 'alice', 'bob' ],
	10,
	OptionsQuestion::GRADING_TYPE_ONE_OF
);

assert( $question->score( [ 'alice' ] ) == 10 );
assert( $question->score( [ 'ALICE' ] ) == 10 ); // case insensitive
assert( $question->score( [ 'bob' ] ) == 10 );
assert( $question->score( [ 'carol' ] ) == 0 );
assert( $question->score( [ 'alice', 'bob' ] ) == 0 ); // only one answer is allowed
assert( $question->score( [ 'alicia' ] ) == 0 ); // alicia is a bit too different
assert( $question->score( [ 'bobb' ] ) == 10 ); // bobb is okay
assert( $question->score( [ 'carol' ] ) == 0 );
assert( $question->score( [ 'trudy' ] ) == 0 );


$question = new OptionsQuestion(
	null,
	null,
	[ 'alice', 'bob', 'carol', 'dave', 'emily' ],
	false,
	0,
	0,
	0,
	10,
	[ 'alice', 'bob' ],
	10,
	OptionsQuestion::GRADING_TYPE_ALL_OF
);

assert( $question->score( [ 'alice' ] ) == 0 );
assert( $question->score( [ 'bob' ] ) == 0 );
assert( $question->score( [ 'carol' ] ) == 0 );
assert( $question->score( [ 'alice', 'bob' ] ) == 10 );
assert( $question->score( [ 'alice', 'bobb' ] ) == 10 ); // bobb is okay
assert( $question->score( [ 'ALICE', 'BOB' ] ) == 10 ); // case insensitive
assert( $question->score( [ 'alice', 'bob', 'carol' ] ) == 0 ); // one incorrect choice -- all wrong

