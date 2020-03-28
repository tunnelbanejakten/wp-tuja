<?php

namespace tuja\frontend\router;


use tuja\data\model\Group;
use tuja\frontend\FrontendView;
use tuja\frontend\PointsOverride;
use tuja\util\Id;

class PointsOverrideInitiator implements ViewInitiator {
	const ACTION = 'rapportera-poang';

	public static function link( Group $group, int $form_id ) {
		return join( '/', [ get_site_url(), $group->random_id, self::ACTION, $form_id ] );
	}

	function create_page( $path ): FrontendView {
		list ( $group_key, , $form_id ) = explode( '/', urldecode( $path ) );

		return new PointsOverride( $path, $group_key, intval( $form_id ) );
	}

	function is_handler( $path ): bool {
		$parts = explode( '/', urldecode( $path ) );
		if ( count( $parts ) < 3 ) {
			return false;
		}
		list ( $group_key, $action, $form_id ) = $parts;

		return isset( $group_key ) && isset( $form_id ) && isset( $action )
		       && $action == self::ACTION
		       && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $group_key )
		       && preg_match( '/^\d+$/', $form_id );
	}
}