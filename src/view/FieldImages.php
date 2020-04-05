<?php

namespace tuja\view;


use tuja\data\model\Group;
use tuja\Frontend;
use tuja\util\ImageManager;

class FieldImages extends Field {
	private $image_manager;
	private $max_files_count;

	function __construct( $label, $hint = null, $read_only = false, $max_files_count = 2 ) {
		parent::__construct( $label, $hint, $read_only );
		$this->image_manager = new ImageManager();
		$this->max_files_count = $max_files_count;
	}

	public function get_posted_answer( $form_field ) {
		if ( isset( $_POST[ $form_field ] ) ) {
			$data = sanitize_post( $_POST[ $form_field ] );

			return [
				'images'  => @$data['images'],
				'comment' => @$data['comment']
			];
		}

		return null;
	}

	public function render( $field_name, $answer_object, Group $group = null, $error_message = '' ) {
		$hint = isset( $this->hint ) ? sprintf( '<small class="tuja-question-hint">%s</small>', $this->hint ) : '';

		return sprintf(
			'<div class="tuja-field tuja-fieldimages"><label>%s%s</label>%s<div><small class="tuja-question-hint tuja-fieldimages-counter"></small></div>%s</div>',
			$this->label,
			$hint,
			$this->render_image_upload( $field_name, $group->random_id, $answer_object ),
			! empty( $error_message ) ? sprintf( '<div class="tuja-message tuja-message-error">%s</div>', $error_message ) : ''
		);
	}


	private function render_comment_field( $field_name, $comment ) {
		ob_start();
		printf(
			'<textarea rows="3" id="%s" name="%s[comment]" placeholder="Skriv kommentar här..." %s>%s</textarea>',
			$field_name . '-comment',
			$field_name,
			$this->read_only ? ' disabled="disabled"' : '',
			$comment
		);

		return ob_get_clean();
	}

	private function render_image_upload( $field_name, $group_key, $answer_object ) {
		Frontend::use_script( 'jquery' );
		Frontend::use_script( 'tuja-exif-js.min.js' ); // Including exif-js automatically enables auto-rotation in Dropzone.
		Frontend::use_script( 'tuja-dropzone.min.js' );
		Frontend::use_script( 'tuja-upload.js' );
		Frontend::use_stylesheet( 'tuja-dropzone.min.css' );

		if ( is_array( $answer_object ) && ! @is_array( $answer_object[0] ) && ! empty( $answer_object[0] ) ) {
			// Fix legacy format (JSON as string in array)
			$answer_object = json_decode( $answer_object[0], true );
		}

		$images = [];
		if ( isset( $answer_object ) && isset( $answer_object['images'] ) ) {
			if ( ! empty( $answer_object['images'] ) ) {
				foreach ( $answer_object['images'] as $filename ) {

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
            <div class="tuja-image-select dropzone"></div>
            <div class="tuja-image-options">
				<?php echo $this->render_comment_field( $field_name, isset( $answer_object ) ? $answer_object['comment'] : '' ); ?>
            </div>
        </div>
		<?php

		return ob_get_clean();
	}
}
