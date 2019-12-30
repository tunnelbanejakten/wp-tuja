<?php

namespace tuja\frontend\router;


use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\frontend\Form;
use tuja\frontend\GroupEditor;
use tuja\frontend\FrontendView;
use tuja\frontend\PersonEditor;
use tuja\util\Id;

class FormInitiator implements ViewInitiator {
	const ACTION = 'svara';

	public static function link( Group $group, int $form_id ) {
		return join( '/', [ get_site_url(), $group->random_id, self::ACTION, $form_id ] );
	}

	function create_page( $path ): FrontendView {
		list ( $group_key, $action, $form_id ) = explode( '/', urldecode( $path ) );

		return new Form( $path, $group_key, intval( $form_id ) );
	}

	function is_handler( $path ): bool {
		list ( $group_key, $action, $form_id ) = explode( '/', urldecode( $path ) );

		return isset( $group_key ) && isset( $form_id ) && isset( $action )
		       && $action == self::ACTION
		       && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $group_key )
		       && preg_match( '/^\d+$/', $form_id );
	}
}