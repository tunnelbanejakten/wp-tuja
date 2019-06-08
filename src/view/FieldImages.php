<?php

namespace tuja\view;


use tuja\data\model\Group;
use tuja\util\ImageManager;

class FieldImages extends Field
{
	const SHORT_LIST_LIMIT = 5;

	private $image_manager;

	function __construct( $key, $label, $hint = null, $read_only = false ) {
		parent::__construct( $key, $label, $hint, $read_only );
		$this->image_manager = new ImageManager();
	}

	public function get_posted_answer( $form_field ) {
		$answer = array();
		if (isset($_POST[$form_field])) {
			$data   = sanitize_post( $_POST[ $form_field ] );
			$answer = array(
				'images'  => $data['images'],
				'comment' => $data['comment']
			);
			$answer = json_encode($answer);
		}

		return array($answer);
	}

	public function render( $field_name, $answer_object, Group $group = null ) {
		$hint = isset($this->hint) ? sprintf('<small class="tuja-question-hint">%s</small>', $this->hint) : '';

		return sprintf(
			'<div class="tuja-field tuja-%s"><label>%s%s</label>%s</div>',
			'fieldimages',
			$this->label,
			$hint,
			$this->render_image_upload( $field_name, $group->random_id, $answer_object )
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
		wp_enqueue_script('tuja-dropzone');
		wp_enqueue_script('tuja-upload-script');

		$images = array();
		if ( $answer_object && is_array( $answer_object ) && ! is_array( $answer_object[0] ) && ! empty( $answer_object[0] ) ) {
			$answer = json_decode($answer_object[0], true);
			if (!empty($answer['images'])) {
				foreach ($answer['images'] as $filename) {

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

		if (empty($images)) {
			$images[] = sprintf('<input type="hidden" name="%s[images][]" value="">', $field_name);
		}

		ob_start();
		?>
        <div class="tuja-image" id="<?php echo $field_name; ?>">
            <div class="tuja-image-select dropzone"></div>
			<?php echo implode('', $images); ?>
            <div class="tuja-image-options">
				<?php echo $this->render_comment_field($field_name, isset($answer) ? $answer['comment'] : ''); ?>
                <button type="button" class="clear-image-field">Rensa bilder</button>
            </div>
        </div>
		<?php

		return ob_get_clean();
	}
}
