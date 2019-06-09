<?php

namespace tuja\util;


use ReflectionClass;
use ReflectionProperty;

class ReflectionUtils {
	public static function get_editable_properties( $obj ) {
		$cls = new ReflectionClass( $obj );

		$is_editable_filter = function ( ReflectionProperty $prop ) {
			return strpos( $prop->getDocComment(), '@tuja-gui-editable' ) !== false;
		};

		$field_mapper = function ( ReflectionProperty $prop ) use ( $obj ) {
			$prop->setAccessible( true );

			return [
				'name'     => $prop->getName(),
				'datatype' => gettype( $prop->getValue( $obj ) ),
			];
		};

		return array_map( $field_mapper, array_filter( $cls->getProperties(), $is_editable_filter ) );
	}

	public static function set_properties_from_json_string( $obj, $json_string ) {
		$editable_properties = self::get_editable_properties( $obj );

		$values = json_decode( $json_string, true );
		foreach ( $editable_properties as $prop_conf ) {
			switch ( $prop_conf['datatype'] ) {
				case 'integer':
				case 'double':
					$obj->{$prop_conf['name']} = floatval( $values[ $prop_conf['name'] ] );
					break;
				default:
					$obj->{$prop_conf['name']} = $values[ $prop_conf['name'] ];
					break;
			}
		}
	}

	public static function get_editable_properties_json( $obj ) {
		$editable_properties = self::get_editable_properties( $obj );

		$props = array_combine(
			array_map( function ( $prop ) {
				return $prop['name'];
			}, $editable_properties ),
			array_map( function ( $prop ) use ( $obj ) {
				return $obj->{$prop['name']};
			}, $editable_properties ) );

		ksort( $props );
		$json = json_encode( $props, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		return $json;
	}
}