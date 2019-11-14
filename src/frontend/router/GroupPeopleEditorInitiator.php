<?php

namespace tuja\frontend\router;


use tuja\data\model\Group;
use tuja\frontend\GroupEditor;
use tuja\frontend\FrontendView;
use tuja\frontend\GroupPeopleEditor;
use tuja\util\Id;

class GroupPeopleEditorInitiator implements ViewInitiator {
	const ACTION = 'andra-personer';

	public static function link( Group $group ) {
		return join( '/', [ get_site_url(), $group->random_id, self::ACTION ] );
	}

	function create_page( $path ): FrontendView {
		list ( $group_key ) = explode( '/', urldecode( $path ) );

		return new GroupPeopleEditor( $path, $group_key );
	}

	function is_handler( $path ): bool {
		list ( $group_key, $action ) = explode( '/', urldecode( $path ) );

		wp_enqueue_script( 'tuja-editgroup-script' ); // Needed?

		return isset( $group_key ) && isset( $action )
		       && $action == self::ACTION
		       && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $group_key );
	}
}