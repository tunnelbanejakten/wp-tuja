<?php

namespace tuja\view;

use Exception;
use tuja\data\model\Group;
use tuja\Frontend;
use tuja\util\ImageManager;
use tuja\util\Strings;

class FieldImages extends Field {
	private $image_manager;
	private $max_files_count;

	const DATA_URI_PATTERN = '/data:(?<mimetype>[a-z]+\\/[a-z]+);base64,(?<data>.*)/';
	const MIME_TYPE_JPEG   = 'image/jpeg';
	const MIME_TYPE_PNG    = 'image/png';

	function __construct( $label, $hint = null, $read_only = false, $max_files_count = 2 ) {
		parent::__construct( $label, $hint, $read_only );
		$this->image_manager   = new ImageManager();
		$this->max_files_count = $max_files_count;
	}

	public function get_data( string $field_name, $stored_posted_answer, Group $group ) {
		if ( isset( $_POST[ $field_name ] ) ) {
			$data = sanitize_post( $_POST[ $field_name ] );

			$images = array_reduce(
				@$data['images'] ?: array(),
				function ( array $res, string $image ) use ( $group ) {
					$matches = array();
					preg_match( self::DATA_URI_PATTERN, $image, $matches );
					if ( isset( $matches['mimetype'] ) && isset( $matches['data'] ) ) {
						$is_jpeg = self::MIME_TYPE_JPEG === $matches['mimetype'];
						$is_png  = self::MIME_TYPE_PNG === $matches['mimetype'];
						if ( $is_jpeg || $is_png ) {
							$save_result = $this->image_manager->save_base64encoded_file(
								$matches['data'],
								$is_png ? IMG_PNG : IMG_JPG,
								$group
							);
							if ( ! $save_result['error'] ) {
								$res[] = $save_result['image'];
								return $res;
							} else {
								throw new Exception( $save_result['error'] );
							}
						} else {
							throw new Exception( Strings::get( 'field_images.unsupported_file_format' ) );
						}
					} else {
						$res[] = $image;
						return $res;
					}
				},
				array()
			);

			return array(
				'images'  => $images,
				'comment' => stripslashes( @$data['comment'] ?: '' ),
			);
		}

		if ( is_array( $stored_posted_answer ) && ! @is_array( $stored_posted_answer[0] ) && ! empty( $stored_posted_answer[0] ) ) {
			// Fix legacy format (JSON as string in array)
			$stored_posted_answer = json_decode( $stored_posted_answer[0], true );
		}

		return array(
			'images'  => @$stored_posted_answer['images'] ?: array(),
			'comment' => @$stored_posted_answer['comment'] ?: '',
		);
	}

	public function render( $field_name, $answer_object, Group $group = null, $error_message = '' ) {
		$label_and_hint = $this->label_with_hint_html( $field_name );

		return sprintf(
			'<div class="tuja-field tuja-fieldimages">%s%s%s</div>',
			$label_and_hint,
			$this->render_image_upload( $field_name, $group->random_id, $this->get_data( $field_name, $answer_object, $group ) ),
			! empty( $error_message ) ? sprintf( '<div class="tuja-message tuja-message-error">%s</div>', $error_message ) : ''
		);
	}


	private function render_comment_field( $field_name, $comment ) {
		ob_start();
		printf(
			'<textarea rows="3" id="%s" name="%s[comment]" placeholder="%s" %s>%s</textarea>',
			$field_name . '-comment',
			$field_name,
			Strings::get( 'field_images.comment.placeholder' ),
			$this->read_only ? ' disabled="disabled"' : '',
			$comment
		);

		return ob_get_clean();
	}

	private function render_image_upload( $field_name, $group_key, $data ) {
		Frontend::use_script( 'jquery' );
		Frontend::use_script( 'tuja-exif-js.min.js' ); // Including exif-js automatically enables auto-rotation in Dropzone.
		Frontend::use_script( 'tuja-dropzone.min.js' );
		Frontend::use_script( 'tuja-upload.js' );
		Frontend::use_stylesheet( 'tuja-dropzone.min.css' );

		$images = array();
		if ( isset( $data ) && isset( $data['images'] ) ) {
			if ( ! empty( $data['images'] ) ) {
				foreach ( $data['images'] as $filename ) {

					$resized_image_url = $this->image_manager->get_resized_image_url(
						$filename,
						ImageManager::DEFAULT_THUMBNAIL_PIXEL_COUNT,
						$group_key
					);

					$images[] = array(
						'filename'        => $filename,
						'resizedImageUrl' => $resized_image_url ? basename( $resized_image_url ) : '',
					);
				}
			}
		}

		ob_start();
		?>
		<div class="tuja-image"
			 data-upload-url="<?php echo admin_url( 'admin-ajax.php' ); ?>"
			 data-base-image-url="<?php echo wp_get_upload_dir()['baseurl'] . '/tuja/'; ?>"
			 data-field-name="<?php echo $field_name; ?>[images][]"
			 data-max-files-count="<?php echo $this->max_files_count; ?>"
			 data-preexisting="<?php echo htmlspecialchars( json_encode( $images ) ); ?>">
			<div>
				<div class="tuja-image-select dropzone"></div>

				<div class="tuja-item-buttons">
					<button type="button" class="tuja-image-add">Lägg till bild</button>
					<span class="tuja-fieldimages-counter"></span>
				</div>
			</div>
			<div class="tuja-image-options">
				<?php echo $this->render_comment_field( $field_name, isset( $data ) ? $data['comment'] : '' ); ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}
}
