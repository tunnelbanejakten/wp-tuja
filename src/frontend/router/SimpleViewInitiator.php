<?php

namespace tuja\frontend\router;


use tuja\data\model\Competition;
use tuja\frontend\CompetitionSignup;
use tuja\frontend\FrontendView;
use tuja\util\Id;

abstract class SimpleViewInitiator implements ViewInitiator {
	private $view_name;

	public function __construct( $view_name ) {
		$this->view_name = $view_name;
	}

	public static function raw_link( string $id, string $view_name ) {
		return join( '/', [ get_site_url(), $id, $view_name ] );
	}

	function create_page( $path ): FrontendView {
		list ( $id ) = explode( '/', urldecode( $path ) );

		return $this->create_page_view( $path, $id );
	}

	abstract function create_page_view( string $path, string $id ): FrontendView;

	function is_handler( $path ): bool {
		$parts = explode( '/', urldecode( $path ) );
		if ( count( $parts ) < 2 ) {
			return false;
		}
		list ( $id, $action ) = $parts;

		return isset( $id ) && isset( $action )
		       && $action == $this->view_name
		       && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $id );
	}
}