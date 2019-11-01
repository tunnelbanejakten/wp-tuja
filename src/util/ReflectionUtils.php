<?php

namespace tuja\util;


use ReflectionClass;
use ReflectionProperty;

class ReflectionUtils {
	public static function set_properties_from_json_string( $obj, $json_string, $json_schema_string ) {
		$values = json_decode( $json_string, true );
		$schema = json_decode( $json_schema_string, true );

		$editable_properties = array_keys( $schema['properties'] );
		foreach ( $editable_properties as $prop_conf ) {
			$obj->{$prop_conf} = $values[ $prop_conf ];
		}
	}

	public static function to_json_string( $obj, $prop_names ) {
		$props = array_combine(
			array_map( function ( $prop ) {
				return $prop;
			}, $prop_names ),
			array_map( function ( $prop ) use ( $obj ) {
				return $obj->{$prop};
			}, $prop_names ) );

		$json = json_encode( $props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		return $json;
	}
}