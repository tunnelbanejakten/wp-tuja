<?php

namespace tuja\data\trait;

use tuja\util\ReflectionUtils;

trait SetPropertiesTrait {
	public function set_properties_from_array( $props ) {
		foreach($this as $key => &$val) {
			if($key === 'name' || $key === 'id' || strpos($key, '_id') !== false) continue;

			if(array_key_exists($key, $props)) {
				if(is_array($props[$key])) {
					$val = array_map('wp_kses_post', $props[$key]);
				} else {
					$val = wp_kses_post($props[$key]);
				}
			} elseif(is_array($val)) {
				$val = [];
			} elseif(is_string($val)) {
				$val = "";
			} elseif(is_numeric($val)) {
				$val = 0;
			} elseif(is_bool($val)) {
				$val = false;
			} else {
				$val = null;
			}
		}
	}

	public function set_properties_from_json_string( $json_string ) {
		ReflectionUtils::set_properties_from_json_string(
			$this,
			$json_string,
			$this->json_schema()
		);
	}

	public function get_editable_properties_json() {
		$schema = json_decode( $this->json_schema(), true );

		$editable_properties = array_keys( $schema['properties'] );

		return ReflectionUtils::to_json_string( $this, $editable_properties );
	}

	public function json_schema() {
		$class_name = end(explode('\\', get_class($this)));
		$dir = rtrim(str_replace(basename(__DIR__), '', __DIR__), '/');
		$str = "$dir/schema/$class_name.schema.json";

		return file_get_contents( $str );
	}
}