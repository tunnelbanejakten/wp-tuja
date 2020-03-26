<?php

namespace tuja\frontend\router;


use tuja\data\model\Group;
use tuja\frontend\FrontendView;
use tuja\frontend\GroupCancelSignup;

class GroupCancelSignupInitiator extends SimpleViewInitiator {
	const ACTION = 'avanmal';

	public function __construct() {
		parent::__construct( self::ACTION );
	}

	public static function link( Group $group ) {
		return SimpleViewInitiator::raw_link( $group->random_id, self::ACTION );
	}

	function create_page_view( string $path, string $id ): FrontendView {
		return new GroupCancelSignup( $path, $id );
	}
}