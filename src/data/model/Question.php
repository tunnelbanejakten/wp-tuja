<?php

namespace tuja\data\model;


use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\question\OptionsQuestion;
use tuja\data\model\question\TextQuestion;

class Question
{
	public static function text( $text, $hint = null, $answer = null ): AbstractQuestion {
		return new TextQuestion( $text, $hint );
	}

	public static function email( $text, $hint = null, $answer = null ): AbstractQuestion {
		return new TextQuestion( $text, $hint, TextQuestion::VALIDATION_EMAIL );
	}

	public static function phone( $text, $hint = null, $answer = null ): AbstractQuestion {
		return new TextQuestion( $text, $hint, TextQuestion::VALIDATION_PHONE );
	}

	public static function pno( $text, $hint = null, $answer = null ): AbstractQuestion {
		return new TextQuestion( $text, $hint, TextQuestion::VALIDATION_PNO );
	}

	public static function dropdown( $text, $options, $hint = null, $answer = null ): AbstractQuestion {
		return new OptionsQuestion( $text, $options, $hint, true, true );
	}

	public static function checkboxes( $text, $options, $hint = null, $answer = null ): AbstractQuestion {
		return new OptionsQuestion( $text, $options, $hint, false, true );
	}
}