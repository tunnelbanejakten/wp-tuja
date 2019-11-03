<?php

namespace tuja\frontend;


class Test extends FrontendPage {
	private $title;

	public function __construct( $url, $title ) {
		parent::__construct( $url );
		$this->title = $title;
	}

	function render() {
		$title = $this->title;
		include( 'views/test.php' );
	}

	function get_title() {
		return 'Page ' . $this->title;
	}
}