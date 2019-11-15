<?php

namespace tuja\frontend\router;


use tuja\data\model\Group;
use tuja\frontend\GroupEditor;
use tuja\frontend\FrontendView;
use tuja\frontend\GroupSignup;
use tuja\util\Id;

class GroupSignupInitiator implements ViewInitiator {
	const ACTION = 'anmal-mig';

	public static function link( Group $group ) {
		return join( '/', [ get_site_url(), $group->random_id, self::ACTION ] );
	}

	function create_page( $path ): FrontendView {
		list ( $group_key ) = explode( '/', urldecode( $path ) );

		return new GroupSignup( $path, $group_key );
	}

	function is_handler( $path ): bool {
		list ( $group_key, $action ) = explode( '/', urldecode( $path ) );

		return isset( $group_key ) && isset( $action )
		       && $action == self::ACTION
		       && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $group_key );
	}
}