<?php

namespace tuja\frontend\router;


use tuja\data\model\Group;
use tuja\frontend\GroupEditor;
use tuja\frontend\FrontendView;
use tuja\frontend\GroupSignup;
use tuja\util\Id;

class GroupSignupInitiator extends SimpleViewInitiator {
	const ACTION = 'anmal-mig';

	public function __construct() {
		parent::__construct( self::ACTION );
	}

	public static function link( Group $group, Group $referrer_group = null ) {
		$url = SimpleViewInitiator::raw_link( $group->random_id, self::ACTION );
		if ( isset( $referrer_group ) ) {
			return $url . '?ref=' . $referrer_group->random_id;
		} else {
			return $url;
		}
	}

	function create_page_view( string $path, string $id ): FrontendView {
		return new GroupSignup( $path, $id );
	}
}
