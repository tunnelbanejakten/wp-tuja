<?php

namespace tuja\frontend\router;


use tuja\data\model\Competition;
use tuja\frontend\CompetitionSignup;
use tuja\frontend\FrontendView;
use tuja\util\Id;

abstract class SimpleViewInitiator implements ViewInitiator {
	private $view_name;

	public function __construct($view_name) {
		$this->view_name = $view_name;
	}

	public static function link( string $id, string $view_name ) {
		return join( '/', [ get_site_url(), $id, $view_name ] );
	}

	function create_page( $path ): FrontendView {
		list ( $id ) = explode( '/', urldecode( $path ) );

		return $this->create_page_view( $path, $id );
	}

	abstract function create_page_view( string $path, string $id ) : FrontendView;

	function is_handler( $path ): bool {
		list ( $id, $action ) = explode( '/', urldecode( $path ) );

		return isset( $id ) && isset( $action )
		       && $action == $this->view_name
		       && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $id );
	}
}