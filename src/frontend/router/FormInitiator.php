<?php

namespace tuja\frontend\router;


use tuja\frontend\Form;
use tuja\frontend\FrontendView;
use tuja\util\Id;

class FormInitiator implements ViewInitiator {
	const ACTION = 'svara';

	public static function link( \tuja\data\model\Group $group, \tuja\data\model\Form $form ) {
		return join( '/', [ get_site_url(), $group->random_id, self::ACTION, $form->random_id ] );
	}

	function create_page( $path ): FrontendView {
		list ( $group_key, $action, $form_key ) = explode( '/', urldecode( $path ) );

		return new Form( $path, $group_key, $form_key );
	}

	function is_handler( $path ): bool {
		list ( $group_key, $action, $form_key ) = explode( '/', urldecode( $path ) );

		return isset( $group_key ) && isset( $form_key ) && isset( $action )
		       && $action == self::ACTION
		       && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $group_key )
		       && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $form_key );
	}
}