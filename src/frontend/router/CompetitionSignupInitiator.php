<?php

namespace tuja\frontend\router;


use tuja\data\model\Competition;
use tuja\frontend\CompetitionSignup;
use tuja\frontend\FrontendView;
use tuja\util\Id;

class CompetitionSignupInitiator implements ViewInitiator {
	const ACTION = 'anmal';

	public static function link( Competition $competition ) {
		return join( '/', [ get_site_url(), $competition->random_id, self::ACTION ] );
	}

	function create_page( $path ): FrontendView {
		list ( $competition_key ) = explode( '/', urldecode( $path ) );

		return new CompetitionSignup( $path, $competition_key );
	}

	function is_handler( $path ): bool {
		list ( $competition_key, $action ) = explode( '/', urldecode( $path ) );

		return isset( $competition_key ) && isset( $action )
		       && $action == self::ACTION
		       && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $competition_key );
	}
}