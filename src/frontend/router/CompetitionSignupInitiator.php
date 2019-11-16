<?php

namespace tuja\frontend\router;


use tuja\data\model\Competition;
use tuja\frontend\CompetitionSignup;
use tuja\frontend\FrontendView;
use tuja\util\Id;

class CompetitionSignupInitiator extends SimpleViewInitiator {
	const ACTION = 'anmal';

	public function __construct() {
		parent::__construct( self::ACTION );
	}

	public static function link( Competition $competition ) {
		return SimpleViewInitiator::link( $competition->random_id, self::ACTION );
	}

	function create_page_view( string $path, string $id ): FrontendView {
		return new CompetitionSignup( $path, $id );
	}
}
