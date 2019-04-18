<?php

namespace tuja\util\score;


class ScoreResult {
	public $total_final = 0;
	public $total_without_question_group_max_limits = 0;
	public $questions = [];
}

class ScoreQuestionResult {
	public $final = 0;
	public $auto = 0;
	public $override = 0;
}