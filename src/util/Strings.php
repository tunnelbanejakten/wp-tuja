<?php


namespace tuja\util;


use Exception;
use tuja\data\store\StringsDao;

class Strings {
	private static $final_list = null;
	private static $default_list = null;
	private static $override_list = null;

	public static function init( int $competition_id, bool $force = false ) {
		if ( self::$default_list == null || $force ) {
			self::$default_list  = parse_ini_file( __DIR__ . '/strings.ini' );
			self::$override_list = ( new StringsDao() )->get_all( $competition_id );
			self::$final_list    = array_merge( self::$default_list, self::$override_list );
			ksort( self::$final_list );
		}
	}

	public static function get( string $key, ...$args ): string {
		if ( self::$final_list == null ) {
			throw new Exception( 'Strings::init() has not been called.' );
		}

		$value = @self::$final_list[ $key ];

		return isset( $value ) ? sprintf( $value, ...$args ) : "[$key]";
	}

	public static function get_list() {
		if ( self::$final_list == null ) {
			throw new Exception( 'Strings::init() has not been called.' );
		}

		return self::$final_list;
	}

	public static function get_default_list() {
		if ( self::$default_list == null ) {
			throw new Exception( 'Strings::init() has not been called.' );
		}

		return self::$default_list;
	}

	public static function get_override_list() {
		if ( self::$override_list == null ) {
			throw new Exception( 'Strings::init() has not been called.' );
		}

		return self::$override_list;
	}
}