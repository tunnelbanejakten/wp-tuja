<?php

namespace tuja\frontend\router;


use tuja\data\model\Group;
use tuja\frontend\GroupHome;
use tuja\frontend\FrontendView;
use tuja\util\Id;

class GroupHomeInitiator implements ViewInitiator {
	public static function link( Group $group ) {
		return join( '/', [ get_site_url(), $group->random_id ] );
	}

	function create_page( $path ): FrontendView {
		return new GroupHome( $path, urldecode( $path ) );
	}

	function is_handler( $path ): bool {
		return preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $path );
	}
}