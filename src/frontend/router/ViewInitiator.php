<?php

namespace tuja\frontend\router;

use tuja\frontend\FrontendView;

interface ViewInitiator {
	function is_handler( $path ): bool;

	function create_page( $path ): FrontendView;
}