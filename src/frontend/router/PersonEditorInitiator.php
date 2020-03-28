<?php

namespace tuja\frontend\router;


use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\frontend\GroupEditor;
use tuja\frontend\FrontendView;
use tuja\frontend\PersonEditor;
use tuja\util\Id;

class PersonEditorInitiator implements ViewInitiator {
	const ACTION = 'andra';

	public static function link( Group $group, Person $person ) {
		return join( '/', [ get_site_url(), $group->random_id, $person->random_id, self::ACTION ] );
	}

	function create_page( $path ): FrontendView {
		list ( $group_key, $person_key, $action ) = explode( '/', urldecode( $path ) );

		return new PersonEditor( $path, $group_key, $person_key );
	}

	function is_handler( $path ): bool {
		$parts = explode( '/', urldecode( $path ) );
		if ( count( $parts ) < 3 ) {
			return false;
		}
		list ( $group_key, $person_key, $action ) = $parts;

		return isset( $group_key ) && isset( $person_key ) && isset( $action )
		       && $action == self::ACTION
		       && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $group_key )
		       && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $person_key );
	}
}