<?php

namespace tuja\util;

use Error;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Upload;
use tuja\data\model\UploadId;
use tuja\data\store\GroupDao;
use tuja\data\store\UploadDao;
use tuja\data\store\ResponseDao;
use tuja\frontend\FormLockValidator;
use tuja\util\concurrency\LockValuesList;

class ImageManager {
	const DEFAULT_THUMBNAIL_PIXEL_COUNT = 200 * 200;
	const DEFAULT_LARGE_PIXEL_COUNT     = 1000 * 1000;
	const FOLDER_NAME                   = 'tuja';

	private $directory;
	private $public_url_directory;
	private $uploads_dao;

	public function __construct() {
		$dir = wp_upload_dir( self::FOLDER_NAME, true, false );
		if ( ! isset( $dir['path'] ) ) {
			throw new Exception( 'Could not find folder to put image in.' );
		}
		$this->directory            = trailingslashit( trailingslashit( $dir['basedir'] ) . self::FOLDER_NAME );
		$this->public_url_directory = trailingslashit( trailingslashit( $dir['baseurl'] ) . self::FOLDER_NAME );
		$this->uploads_dao          = new UploadDao();
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

	public function get_url_from_absolute_path( $path ): string {
		return $this->public_url_directory . substr( $path, strlen( $this->directory ) );
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
		@list ( $file_id, $ext ) = explode( '.', $filename );
		if ( empty( $file_id ) || empty( $ext ) ) {
			return false;
		}
		$dst_filename  = "$file_id-$pixels.$ext";
		$sub_directory = isset( $group_key ) ? "group-$group_key/" : '';
		$dst_path      = $this->directory . $sub_directory . $dst_filename;
		if ( file_exists( $dst_path ) ) {
			return $this->public_url_directory . $sub_directory . $dst_filename;
		}

		$src_path     = $this->directory . $sub_directory . $filename;
		$image_editor = wp_get_image_editor( $src_path );
		if ( is_wp_error( $image_editor ) ) {
			error_log( 'Could not create wp_get_image_editor for ' . $src_path . ' because: ' . $image_editor->get_error_message() );

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
		$image_editor->set_quality( 100 );
		$image_editor->resize( $dst_width, $dst_height, false );
		$saved = $image_editor->save( $dst_path );
		if ( is_wp_error( $saved ) ) {
			return false;
		} else {
			$resized_image_url = $this->public_url_directory . $sub_directory . $dst_filename;

			$upload_id = new UploadId( $group_key, md5_file( $src_path ) );
			$this->uploads_dao->create_version( $upload_id, $dst_path, 'resized_' . $pixels . '_path' );

			return $resized_image_url;
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
		$filenames = array_filter(
			scandir( $old_dir ),
			function ( $filename ) use ( $file_id ) {
				return strpos( $filename, $file_id ) !== false;
			}
		);

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

	public function save_base64encoded_file( string $encoded_file, int $image_type, Group $group ) {
		Strings::init( $group->competition_id );

		self::init_wp_upload_dir( $group->random_id );
		$upload_dir = wp_upload_dir();

		$file_content = base64_decode( $encoded_file );
		$file_path    = $upload_dir['path'] . '/' . md5( $file_content ) . ( IMG_PNG === $image_type ? '.png' : '.jpg' );

		$bytes_written = file_put_contents( $file_path, $file_content );

		if ( false !== $bytes_written && $bytes_written > 0 ) {
			return $this->resize_and_return( $file_path, $group );
		}

		return array(
			'error'       => Strings::get( 'image_manager.unknown_error' ),
			'http_status' => 500,
		);
	}

	public function save_uploaded_file( $file_upload_object, Group $group, string $question_id, string $lock_value ) {
		$question = (int) $question_id;
		Strings::init( $group->competition_id );

		$response_dao = new ResponseDao();

		try {
			// Check lock. If lock is empty no answers has been sent so just proceed.
			$current_locks_all     = LockValuesList::from_string( $lock_value );
			$current_locks_updates = $current_locks_all->subset( array( $question ) );

			( new FormLockValidator( $response_dao, $group ) )->check_optimistic_lock( $current_locks_updates );
		} catch ( Exception $e ) {
			return array(
				'error'       => Strings::get( 'image_manager.optimistic_lock_error' ),
				'http_status' => 409,
			);
		}

		self::init_wp_upload_dir( $group->random_id );

		// Normalizes file uploads. $file_upload_object is an associative array where each elements value can be either a string or an array of
		// multiple strings. wp_handle_upload expects each value to be a string and returns an error otherwise, so if the values are
		// arrays we need to turn them into strings. We know that this function will only be called with one uploaded file at a time
		// so it is safe to just take the first element in each value array.
		$file = $file_upload_object;
		if ( isset( $file['error'][0] ) ) {
			$file = array_map(
				function ( $e ) {
					return $e[0];
				},
				$file
			);
		}

		$hash             = md5_file( $file['tmp_name'] );
		$upload_overrides = array(
			'test_form'                => false,
			'unique_filename_callback' => function ( $dir, $name, $ext ) use ( $hash ) {
				return $hash . $ext;
			},
		);

		$movefile = wp_handle_upload( $file, $upload_overrides );

		if ( $movefile && ! isset( $movefile['error'] ) ) {
			return $this->resize_and_return( $movefile['file'], $group );
		}

		return array(
			'error'       => Strings::get( 'image_manager.unknown_error' ),
			'http_status' => 500,
		);
	}

	private function save_upload_record( string $file_path, Group $group ) : int {
		error_log( 'save_upload_record for ' . $file_path );
		$id = new UploadId( $group->random_id, md5_file( $file_path ) );

		$create_result         = $this->uploads_dao->create( $id, $group );
		$create_version_result = $this->uploads_dao->create_version( $id, $file_path, 'original' );

		return $create_result && $create_version_result;
	}

	private function resize_and_return( string $file_path, Group $group ) {
		$group_key            = $group->random_id;
		$file_path_components = explode( '/', $file_path );
		$filename             = array_pop( $file_path_components );

		$record_saved = $this->save_upload_record( $file_path, $group );
		if ( false === $record_saved ) {
			error_log( 'Could not store upload object.' );
			return array(
				'error'       => Strings::get( 'image_manager.unknown_error' ),
				'http_status' => 500,
			);
		}

		$resized_image_url = ( new ImageManager() )->get_resized_image_url(
			$filename,
			self::DEFAULT_THUMBNAIL_PIXEL_COUNT,
			$group_key
		);

		if ( false === $resized_image_url ) {
			error_log( 'Could not resize image.' );
			return array(
				'error'       => Strings::get( 'image_manager.unknown_error' ),
				'http_status' => 500,
			);
		}

		return array(
			'error'         => false,
			'image'         => $filename,
			'thumbnail_url' => $resized_image_url,
			'http_status'   => 200,
		);
	}

	private static function init_wp_upload_dir( string $group_key ) {
		$upload_dir = '/tuja/group-' . $group_key;
		add_filter(
			'upload_dir',
			function ( $dirs ) use ( $upload_dir ) {
				$dirs['subdir'] = $upload_dir;
				$dirs['path']   = $dirs['basedir'] . $upload_dir;
				$dirs['url']    = $dirs['baseurl'] . $upload_dir;

				if ( ! file_exists( $dirs['basedir'] . $upload_dir ) ) {
					mkdir( $dirs['basedir'] . $upload_dir, 0755, true );
				}

				return $dirs;
			}
		);
	}
}
