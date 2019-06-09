<?php

include_once '../src/data/model/question/AbstractQuestion.php';
include_once '../src/data/model/question/NumberQuestion.php';

use \tuja\data\model\question\NumberQuestion;

$question = new NumberQuestion(
	null,
	null,
	0,
	0,
	0,
	10,
	42);

assert( $question->score( 42 ) == 10 );
assert( $question->score( '42' ) == 10 );
assert( $question->score( [ 42 ] ) == 10 );
assert( $question->score( [ '42' ] ) == 10 );

assert( $question->score( 41 ) == 0 );
assert( $question->score( 0 ) == 0 );
assert( $question->score( null ) == 0 );
assert( $question->score( '' ) == 0 );
assert( $question->score( '41' ) == 0 );
assert( $question->score( [ 41 ] ) == 0 );
assert( $question->score( [ 0 ] ) == 0 );
assert( $question->score( [ null ] ) == 0 );
assert( $question->score( [ '' ] ) == 0 );
assert( $question->score( [ 0, 42 ] ) == 0 );
assert( $question->score( [ '41' ] ) == 0 );
