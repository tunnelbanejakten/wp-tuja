<?php

include_once '../src/data/model/Question.php';

use tuja\data\model\Question;

$question = new Question();
$question->correct_answers = ['alice', 'bob'];
$question->possible_answers = ['alice', 'bob', 'carol'];
$question->score_type = Question::QUESTION_GRADING_TYPE_ONE_OF;
$question->score_max = 10;

assert($question->score(['alice']) == 10);
assert($question->score(['bob']) == 10);
assert($question->score(['alice', 'bob']) == 10);
assert($question->score(['ALICE', 'BOB']) == 10);
assert($question->score(['alicia', 'bobb']) == 10); // alicia okay but bobb is not
assert($question->score(['alice', 'bob', 'carol']) == 10);
assert($question->score(['alice', 'carol']) == 10);
assert($question->score(['carol']) == 0);
assert($question->score(['trudy']) == 0);


$question = new Question();
$question->correct_answers = ['alice', 'bob'];
$question->possible_answers = ['alice', 'bob', 'carol'];
$question->score_type = Question::QUESTION_GRADING_TYPE_ALL_OF;
$question->score_max = 10;

assert($question->score(['alice']) == 0);
assert($question->score(['bob']) == 0);
assert($question->score(['alice', 'bob']) == 10);
assert($question->score(['ALICE', 'BOB']) == 10);
assert($question->score(['alicia', 'bobb']) == 0); // alicia okay but bobb is not
assert($question->score(['alice', 'bob', 'carol']) == 0);
assert($question->score(['alice', 'carol']) == 0);
assert($question->score(['carol']) == 0);
assert($question->score(['trudy']) == 0);
