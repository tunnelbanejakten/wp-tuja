<?php

namespace tuja\view;


use tuja\data\model\Group;
use tuja\Frontend;
use tuja\util\ImageManager;
use tuja\util\Strings;

class FieldImages extends Field {
	private $image_manager;
	private $max_files_count;

	function __construct( $label, $hint = null, $read_only = false, $max_files_count = 2 ) {
		parent::__construct( $label, $hint, $read_only );
		$this->image_manager   = new ImageManager();
		$this->max_files_count = $max_files_count;
	}

	public function get_data( string $field_name, $stored_posted_answer ) {
		if ( isset( $_POST[ $field_name ] ) ) {
			$data = sanitize_post( $_POST[ $field_name ] );

			return [
				'images'  => @$data['images'] ?: [],
				'comment' => @$data['comment'] ?: ''
			];
		}

		if ( is_array( $stored_posted_answer ) && ! @is_array( $stored_posted_answer[0] ) && ! empty( $stored_posted_answer[0] ) ) {
			// Fix legacy format (JSON as string in array)
			$stored_posted_answer = json_decode( $stored_posted_answer[0], true );
		}

		return [
			'images'  => @$stored_posted_answer['images'] ?: [],
			'comment' => @$stored_posted_answer['comment'] ?: ''
		];
	}

	public function render( $field_name, $answer_object, Group $group = null, $error_message = '' ) {
		$hint = isset( $this->hint ) ? sprintf( '<small class="tuja-question-hint">%s</small>', $this->hint ) : '';

		return sprintf(
			'<div class="tuja-field tuja-fieldimages"><label>%s%s</label>%s%s</div>',
			$this->label,
			$hint,
			$this->render_image_upload( $field_name, $group->random_id, $this->get_data($field_name, $answer_object) ),
			! empty( $error_message ) ? sprintf( '<div class="tuja-message tuja-message-error">%s</div>', $error_message ) : ''
		);
	}


	private function render_comment_field( $field_name, $comment ) {
		ob_start();
		printf(
			'<textarea rows="3" id="%s" name="%s[comment]" placeholder="%s" %s>%s</textarea>',
			$field_name . '-comment',
			$field_name,
			Strings::get('field_images.comment.placeholder'),
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

		$images = [];
		if ( isset( $data ) && isset( $data['images'] ) ) {
			if ( ! empty( $data['images'] ) ) {
				foreach ( $data['images'] as $filename ) {

					$resized_image_url = $this->image_manager->get_resized_image_url(
						$filename,
						ImageManager::DEFAULT_THUMBNAIL_PIXEL_COUNT,
						$group_key );

					$images[] = [
						'filename'        => $filename,
						'resizedImageUrl' => $resized_image_url ? basename( $resized_image_url ) : ''
					];
				}
			}
		}

		ob_start();
		?>
        <div class="tuja-image"
             data-upload-url="<?= admin_url( 'admin-ajax.php' ) ?>"
             data-base-image-url="<?= wp_get_upload_dir()['baseurl'] . '/tuja/' ?>"
             data-field-name="<?= $field_name ?>[images][]"
             data-max-files-count="<?= $this->max_files_count ?>"
             data-preexisting="<?= htmlspecialchars( json_encode( $images ) ) ?>">
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
