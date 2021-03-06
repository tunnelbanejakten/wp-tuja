<?php

namespace tuja\frontend\router;


use tuja\data\model\Group;
use tuja\frontend\FrontendView;
use tuja\frontend\GroupStatus;

class GroupStatusInitiator extends SimpleViewInitiator {
	const ACTION = 'status';

	public function __construct() {
		parent::__construct( self::ACTION );
	}

	public static function link( Group $group ) {
		return SimpleViewInitiator::raw_link( $group->random_id, self::ACTION );
	}

	function create_page_view( string $path, string $id ): FrontendView {
		return new GroupStatus( $path, $id );
	}
}