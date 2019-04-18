<?php

namespace tuja\view;


use tuja\util\ImageManager;

class FieldImages extends Field
{
	const SHORT_LIST_LIMIT = 5;

	public function get_posted_answer($form_field) {
		$answer = array();
		if (isset($_POST[$form_field])) {
			$data   = sanitize_post($_POST[$form_field]);
			$answer = array(
				'images'  => $data['images'],
				'comment' => $data['comment']
			);
			$answer = json_encode($answer);
		}

		return array($answer);
	}

	public function render($field_name) {
		$hint = isset($this->hint) ? sprintf('<small class="tuja-question-hint">%s</small>', $this->hint) : '';

		return sprintf(
			'<div class="tuja-field tuja-%s"><label>%s%s</label>%s</div>',
			strtolower((new \ReflectionClass($this))->getShortName()),
			$this->label,
			$hint,
			$this->render_image_upload($field_name)
		);
	}

	private function render_comment_field($field_name, $comment) {
		ob_start();
		echo '<label for="' . $field_name . '-comment' . '">Kommentar</label>';
		printf(
			'<textarea rows="3" id="%s" name="%s[comment]" %s>%s</textarea>',
			$field_name . '-comment',
			$field_name,
			$this->read_only ? ' disabled="disabled"' : '',
			$comment
		);

		return ob_get_clean();
	}

	private function render_image_upload($field_name) {
		wp_enqueue_script('tuja-dropzone');
		wp_enqueue_script('tuja-upload-script');

		$images = array();
		if ($this->value) {
			$answer = json_decode($this->value[0], true);
			if (!empty($answer['images'])) {
				foreach ($answer['images'] as $filename) {
					$images[] = sprintf('<input type="hidden" name="%s[images][]" value="%s">', $field_name, $filename);
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
