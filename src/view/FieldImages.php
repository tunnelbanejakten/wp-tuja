<?php

namespace tuja\view;


use tuja\data\model\Group;
use tuja\util\ImageManager;

class FieldImages extends Field
{
	const SHORT_LIST_LIMIT = 5;

	private $image_manager;

	function __construct( $label, $hint = null, $read_only = false ) {
		parent::__construct( $label, $hint, $read_only );
		$this->image_manager = new ImageManager();
	}

	public function get_posted_answer( $form_field ) {
		if (isset($_POST[$form_field])) {
			$data   = sanitize_post( $_POST[ $form_field ] );

			return [
				'images'  => $data['images'],
				'comment' => $data['comment']
			];
		}

		return null;
	}

	public function render( $field_name, $answer_object, Group $group = null, $error_message = '' ) {
		$hint = isset($this->hint) ? sprintf('<small class="tuja-question-hint">%s</small>', $this->hint) : '';

		return sprintf(
			'<div class="tuja-field tuja-%s"><label>%s%s</label>%s%s</div>',
			'fieldimages',
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
		wp_enqueue_script('jquery');
		wp_enqueue_script('tuja-dropzone');
		wp_enqueue_script('tuja-upload-script');

		if ( is_array( $answer_object ) && ! is_array( $answer_object[0] ) && ! empty( $answer_object[0] ) ) {
		    // Fix legacy format (JSON as string in array)
			$answer_object = json_decode( $answer_object[0], true );
		}

		$images = array();
		if ( isset( $answer_object ) && isset( $answer_object['images'] ) ) {
			if ( ! empty( $answer_object['images'] ) ) {
				foreach ( $answer_object['images'] as $filename ) {

					$resized_image_url = $this->image_manager->get_resized_image_url(
						$filename,
						ImageManager::DEFAULT_THUMBNAIL_PIXEL_COUNT,
						$group_key );

					$images[] = sprintf( '<input type="hidden" name="%s[images][]" value="%s" data-thumbnail-url="%s">',
						$field_name,
						$filename,
						$resized_image_url ? basename( $resized_image_url ) : '' );
				}
			}
		}

		if ( empty( $images ) ) {
			$images[] = sprintf( '<input type="hidden" name="%s[images][]" value="">', $field_name );
		}

		ob_start();
		?>
        <div class="tuja-image" id="<?php echo $field_name; ?>">
            <div class="tuja-image-select">
                <div class="dropzone"></div>
                <div class="tuja-item-buttons tuja-item-buttons-center">
                    <button type="button" class="clear-image-field">Ta bort dessa bilder</button>
                </div>
            </div>
			<?php echo implode('', $images); ?>
            <div class="tuja-image-options">
	            <?php echo $this->render_comment_field( $field_name, isset( $answer_object ) ? $answer_object['comment'] : '' ); ?>
            </div>
        </div>
		<?php

		return ob_get_clean();
	}
}
