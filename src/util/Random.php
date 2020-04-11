<?php


namespace tuja\util;


class Random {
	public static function string( array $options ): string {
		return $options[ rand( 0, count( $options ) - 1 ) ];
	}
}