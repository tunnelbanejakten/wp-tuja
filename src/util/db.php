<?php

namespace tuja\util;

use tuja\Plugin;

class DB {
	
	static public function get_table($name) {
		global $wpdb;
			
		return $wpdb->prefix . Plugin::TABLE_PREFIX . $name;
	}
}