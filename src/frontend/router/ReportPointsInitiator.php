<?php

namespace tuja\frontend\router;


use tuja\data\model\Person;
use tuja\data\model\Station;
use tuja\frontend\FrontendView;
use tuja\frontend\ReportPoints;
use tuja\util\Id;

class ReportPointsInitiator implements ViewInitiator {
	const ACTION = 'rapportera';

	public static function link_one( Person $person, Station $station ) {
		return join( '/', array( get_site_url(), $person->random_id, self::ACTION, $station->random_id ) );
	}

	public static function link_all( Person $person ) {
		return join( '/', array( get_site_url(), $person->random_id, self::ACTION ) );
	}

	function create_page( $path ): FrontendView {
		@list ( $person_key, , $station_key ) = explode( '/', urldecode( $path ) );

		return new ReportPoints( $path, $person_key ?? "", $station_key ?? "" );
	}

	function is_handler( $path ): bool {
		$parts                                       = explode( '/', urldecode( $path ) );
		@list ( $person_key, $action, $station_key ) = $parts;

		return ( isset( $action ) && self::ACTION === $action )
			&& ( isset( $person_key ) && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $person_key ) )
			&& ( ! isset( $station_key ) || preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $station_key ) );
	}
}
