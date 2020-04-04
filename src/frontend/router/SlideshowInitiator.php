<?php

namespace tuja\frontend\router;


use tuja\data\model\Competition;
use tuja\frontend\FrontendView;
use tuja\frontend\Slideshow;

class SlideshowInitiator extends SimpleViewInitiator {
	const ACTION = 'bildspel';

	public function __construct() {
		parent::__construct( self::ACTION );
	}

	public static function link( Competition $competition ) {
		return SimpleViewInitiator::raw_link( $competition->random_id, self::ACTION );
	}

	function create_page_view( string $path, string $id ): FrontendView {
		return new Slideshow( $path, $id );
	}
}
