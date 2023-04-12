<?php

namespace tuja\util;

use tuja\data\model\Group;

class AppUtils {
	public static function group_link( Group $group ) {
		return sprintf(
			'%s/#/%s/',
			self::base_link(),
			$group->random_id
		);
	}
	public static function group_checkin_link( Group $group ) {
		return sprintf(
			'%s/#/%s/checkin',
			self::base_link(),
			$group->random_id
		);
	}
	public static function base_link() {
		$is_localhost = strpos( $_SERVER['HTTP_HOST'], 'localhost' ) !== false;
		return $is_localhost ? 'http://localhost:8081' : 'https://app.tunnelbanejakten.se'; // TODO: This shouldn't be hardcoded.
	}
}
