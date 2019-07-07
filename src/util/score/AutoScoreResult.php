<?php

namespace tuja\util\score;


class AutoScoreResult {
	public $score = 0;
	public $confidence = 0.0;

	public function __construct( int $score, float $confidence ) {
		$this->score      = $score;
		$this->confidence = $confidence;
	}
}