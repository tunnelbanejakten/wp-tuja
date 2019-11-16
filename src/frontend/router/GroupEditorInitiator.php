<?php

namespace tuja\frontend\router;


use tuja\data\model\Group;
use tuja\frontend\GroupEditor;
use tuja\frontend\FrontendView;
use tuja\util\Id;

class GroupEditorInitiator extends SimpleViewInitiator {
	const ACTION = 'andra';

	public function __construct() {
		parent::__construct( self::ACTION );
	}

	public static function link( Group $group ) {
		return SimpleViewInitiator::link( $group->random_id, self::ACTION );
	}

	function create_page_view( string $path, string $id ): FrontendView {
		return new GroupEditor( $path, $id );
	}
}