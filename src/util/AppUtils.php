<?php

namespace tuja\util;

use tuja\data\model\Group;

class AppUtils {
	public static function group_link( Group $group ) {
		$is_localhost = strpos( $_SERVER['HTTP_HOST'], 'localhost' ) !== false;
		return sprintf(
			'%s/#/%s/',
			$is_localhost ? 'http://localhost:8081' : 'https://app.tunnelbanejakten.se', // TODO: This shouldn't be hardcoded.
			$group->random_id
		);
	}
}
?>