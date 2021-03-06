<?php

namespace tuja\util;

use Exception;
use tuja\data\store\GroupDao;
use tuja\data\store\ResponseDao;
use tuja\frontend\FormLockValidator;
use tuja\util\concurrency\LockValuesList;

class ImageManager {
	const DEFAULT_THUMBNAIL_PIXEL_COUNT = 200 * 200;

	private $directory;
	private $public_url_directory;

	public function __construct() {
		$dir = wp_upload_dir( 'tuja', true, false );
		if ( ! isset( $dir['path'] ) ) {
			throw new Exception( 'Could not find folder to put image in.' );
		}
		$this->directory            = trailingslashit( $dir['path'] );
		$this->public_url_directory = trailingslashit( $dir['url'] );
	}

	public function import_jpeg( $file_path, $group_key = null ): string {
		if ( file_exists( $file_path ) ) {

			list ( , , $type ) = getimagesize( $file_path );
			if ( $type != IMG_JPG && $type != IMG_PNG ) {
				throw new Exception( 'Unsupported image format.' ); // TODO: i18n
			}

			$image_editor = wp_get_image_editor( $file_path );
			if ( is_wp_error( $image_editor ) ) {
				throw new Exception( 'Not a valid image.' ); // TODO: i18n
			}

			$ext           = $type == IMG_JPG ? 'jpg' : 'png';
			$hash          = md5_file( $file_path );
			$filename      = $hash . '.' . $ext;
			$sub_directory = isset( $group_key ) ? "group-$group_key/" : '';
			if ( ! is_dir( $this->directory . $sub_directory ) ) {
				mkdir( $this->directory . $sub_directory, 0755, true );
			}
			$new_path = $this->directory . $sub_directory . $filename;

			if ( file_exists( $new_path ) ) {
				// This exact file has been uploaded before.
				return $filename;
			}

			if ( $this->move( $file_path, $new_path ) ) {
				return $filename;
			} else {
				throw new Exception( sprintf( 'Could not save uploaded file to %s', $new_path ) ); // TODO: i18n
			}

		} else {
			throw new Exception( sprintf( 'File %s not found', $file_path ) ); // TODO: i18n
		}
	}

	public function get_original_image_url( $filename, $group_key = null ) {
		$sub_directory = isset( $group_key ) ? "group-$group_key/" : '';
		$dst_path      = $this->directory . $sub_directory . $filename;
		if ( file_exists( $dst_path ) ) {
			return $this->public_url_directory . $sub_directory . $filename;
		}

		return false;
	}

	public function get_resized_image_url( $filename, $pixels, $group_key = null ) {
		list ( $file_id, $ext ) = explode( '.', $filename );
		$dst_filename  = "$file_id-$pixels.$ext";
		$sub_directory = isset( $group_key ) ? "group-$group_key/" : '';
		$dst_path      = $this->directory . $sub_directory . $dst_filename;
		if ( file_exists( $dst_path ) ) {
			return $this->public_url_directory . $sub_directory . $dst_filename;
		}

		$src_path     = $this->directory . $sub_directory . "$file_id.$ext";
		$image_editor = wp_get_image_editor( $src_path );
		if ( is_wp_error( $image_editor ) ) {
			if ( $group_key != null ) {
				// The group_key is set but we didn't find the image in the group's sub-directory. Check if it for some reason is still in the root directory.
				return $this->get_resized_image_url( $filename, $pixels, null );
			}

			return false;
		}

		$src_size = $image_editor->get_size();
		if ( is_wp_error( $src_size ) ) {
			return false;
		}
		$width  = $src_size['width'];
		$height = $src_size['height'];

		$dst_width  = sqrt( ( $pixels * $width ) / $height );
		$dst_height = $dst_width * ( $height / $width );
		$image_editor->resize( $dst_width, $dst_height, false );
		$saved = $image_editor->save( $dst_path );
		if ( is_wp_error( $saved ) ) {
			return false;
		} else {
			return $this->public_url_directory . $sub_directory . $dst_filename;
		}
	}

	// TODO: Look into overlap between move() and move_to_group()
	public function move_to_group( $source_filename, $old_group_key, $new_group_key ) {
		$old_sub_directory = isset( $old_group_key ) ? "group-$old_group_key/" : '';
		$old_dir           = $this->directory . $old_sub_directory;

		if ( ! is_dir( $old_dir ) ) {
			// This is not that necessary
			mkdir( $old_dir, 0755, true );
		}

		$new_sub_directory = isset( $new_group_key ) ? "group-$new_group_key/" : '';
		$new_dir           = $this->directory . $new_sub_directory;

		if ( ! is_dir( $new_dir ) ) {
			mkdir( $new_dir, 0755, true );
		}

		list ( $file_id, $ext ) = explode( '.', $source_filename );

		// Get all files, including thumbnails, matching the given $source_filename
		$filenames = array_filter( scandir( $old_dir ), function ( $filename ) use ( $file_id ) {
			return strpos( $filename, $file_id ) !== false;
		} );

		foreach ( $filenames as $filename ) {
			$move_result = rename( $old_dir . $filename, $new_dir . $filename );
		}
	}

	private function move( $old, $new ) {
		if ( is_uploaded_file( $old ) ) {
			return move_uploaded_file( $old, $new );
		} else {
			return rename( $old, $new );
		}
	}

	/**
	 * Handles image uploads from FromShortcode via AJAX.
	 */
	public static function handle_image_upload() {
		if ( ! empty( $_FILES['file'] ) && ! empty( $_POST['group'] ) && ! empty( $_POST['question'] ) ) {
			$group_dao = new GroupDao();
			$group     = $group_dao->get_by_key( sanitize_text_field( $_POST['group'] ) );
			$question  = (int) $_POST['question']; // int or string?
			Strings::init($group->competition_id);

			$response_dao = new ResponseDao();

			try {
				// Check lock. If lock is empty no answers has been sent so just proceed.
				$current_locks_all     = LockValuesList::from_string( $_POST['lock'] );
				$current_locks_updates = $current_locks_all->subset( [ $question ] );

				( new FormLockValidator( $response_dao, $group ) )->check_optimistic_lock( $current_locks_updates );
			} catch ( Exception $e ) {
				wp_send_json( array(
					'error' => Strings::get('image_manager.optimistic_lock_error')
				), 409 );
				exit;
			}

			$upload_dir = '/tuja/group-' . $group->random_id;
			add_filter( 'upload_dir', function ( $dirs ) use ( $upload_dir ) {
				$dirs['subdir'] = $upload_dir;
				$dirs['path']   = $dirs['basedir'] . $upload_dir;
				$dirs['url']    = $dirs['baseurl'] . $upload_dir;

				if ( ! file_exists( $dirs['basedir'] . $upload_dir ) ) {
					mkdir( $dirs['basedir'] . $upload_dir, 0755, true );
				}

				return $dirs;
			} );

			$upload_overrides = array(
				'test_form'                => false,
				'unique_filename_callback' => function ( $dir, $name, $ext ) use ( $question ) {
					$name = md5( $name . $question );

					return $name . $ext;
				}
			);

			// Normalizes file uploads. $_FILES['file'] is an associative array where each elements value can be either a string or an array of
			// multiple strings. wp_handle_upload expects each value to be a string and returns an error otherwise, so if the values are
			// arrays we need to turn them into strings. We know that this function will only be called with one uploaded file at a time
			// so it is safe to just take the first element in each value array.
			$file = $_FILES['file'];
			if ( isset( $file['error'][0] ) ) {
				$file = array_map( function ( $e ) {
					return $e[0];
				}, $file );
			}

			$movefile = wp_handle_upload( $file, $upload_overrides );

			if ( $movefile && ! isset( $movefile['error'] ) ) {
				$filename = explode( '/', $movefile['file'] );
				$filename = array_pop( $filename );

				wp_send_json( array(
					'error' => false,
					'image' => $filename
				) );
				exit;
			}

			wp_send_json( array(
				'error' => Strings::get('image_manager.unknown_error')
			), 500 );
			exit;
		}

		wp_send_json( array(
			'error' => Strings::get('image_manager.invalid_data')
		), 400 );
		exit;
	}
}