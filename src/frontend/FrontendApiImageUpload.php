<?php

namespace tuja\frontend;

use Exception;
use tuja\data\store\GroupDao;
use tuja\util\ImageManager;
use tuja\util\JwtUtils;
use tuja\util\Strings;

class FrontendApiImageUpload {

	/**
	 * Handles image uploads from FormShortcode via AJAX.
	 */
	public static function handle_image_upload() {
		if ( ! empty( $_FILES['file'] ) && ( ! empty( $_POST['group'] ) || ! empty( $_POST['token'] ) ) && ! empty( $_POST['question'] ) ) {

			$group = self::get_group( $_POST['group'] ?? '', $_POST['token'] ?? '' );
			if ( ! empty( $group ) && isset( $group ) ) {
				$result = ImageManager::save_uploaded_file( $_FILES['file'], $group, $_POST['question'], $_POST['lock'] );

				wp_send_json( $result, $result['http_status'] );
				exit;
			}
		}

		wp_send_json(
			array(
				'error' => Strings::get( 'image_manager.invalid_data' ),
			),
			400
		);
		exit;
	}

	/**
	 * Get the team/group based on EITHER a group key OR a client token.
	 */
	private static function get_group( $group_key, $token ) {
		$group_dao = new GroupDao();

		if ( ! empty( $group_key ) ) {
			return $group_dao->get_by_key( sanitize_text_field( $group_key ) );
		} elseif ( ! empty( $token ) ) {
			try {
				$decoded = JwtUtils::decode( $token );

				return $group_dao->get( (int) $decoded->group_id );
			} catch ( Exception $e ) {
				return false;
			}
		}
	}

}
