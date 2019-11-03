<?php

namespace tuja\frontend\router;

use tuja\frontend\FrontendPage;

interface PageInitiator {
	function is_handler( $path ): bool;

	function create_page( $path ): FrontendPage;
}