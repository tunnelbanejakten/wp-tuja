<?php

namespace tuja\admin;

use Exception;

class AdminUtils
{
    /**
     * Prints an error message, with WP's default admin page style, based on an exception.
     */
    public static function printException(Exception $ex) {
        self::printError($ex->getMessage());
	}
	

	public static function printError($message) {
		printf('<div class="notice notice-error is-dismissable" style="margin-left: 2px"><p><strong>%s: </strong>%s</p></div>',
            'Oj, något blev fel',
            $message);
	}
}