<?php

namespace tuja\view;

use Exception;
use tuja\util\ImageManager;

class FieldImagesImage
{
    public $image_id;
    public $option;
    public $comment;

    public function __construct($image_id, $option, $comment)
    {
        $this->image_id = $image_id;
        $this->option = $option;
        $this->comment = $comment;
    }

    public function __toString()
    {
        return join(',', array($this->image_id, $this->option, $this->comment));
    }

    public static function from_string($answer): FieldImagesImage
    {
        list ($image_id, $option, $comment) = explode(',', $answer, 3);
        return new FieldImagesImage($image_id, $option, $comment);
    }
}

class FieldImages extends Field
{
    public $options;
    private $image_manager;

    const SHORT_LIST_LIMIT = 5;

    public function __construct($options)
    {
        parent::__construct();
        $this->options = $options;
        $this->image_manager = new ImageManager();
    }

    public function get_posted_answer($form_field)
    {
        $answer = [];
        // TODO: Extract sub-field property names to constants.
        $is_regular_file_upload_attempted = isset($_FILES["$form_field-NEW-file"]) && $_FILES["$form_field-NEW-file"]['error'] !== UPLOAD_ERR_NO_FILE;
		$is_base64_file_upload_attempted = isset($_POST["$form_field-NEW-file_resized"]);
		
        if ($is_regular_file_upload_attempted || $is_base64_file_upload_attempted) {
            // TODO: A "get method" should not have side-effects, like saving files.
            // Process upload of new image
            try {
                if(isset($_FILES["$form_field-NEW-file"])) {
					$image_path = $this->get_uploaded_file_path($_FILES["$form_field-NEW-file"]);
				} else {
					$image_path = $this->save_base64_file($_POST["$form_field-NEW-file_resized"]);
				}

                if($import_result = $this->image_manager->import_jpeg($image_path)) {
                    $answer['NEW'] = new FieldImagesImage($import_result, $_POST["$form_field-NEW-option"], $_POST["$form_field-NEW-comment"]);
                }
            } catch (Exception $e) {
                if(isset($image_path) && file_exists($image_path)) {
					unlink($image_path);
                }
				
				throw $e;
            }
        }

        foreach ($_POST as $key => $value) {
            if (substr($key, 0, strlen($form_field)) === $form_field) {
                list($index, $sub_field) = explode('-', substr($key, strlen($form_field)), 2);
                if (!isset($answer[$index])) {
                    $answer[$index] = new FieldImagesImage('', '', '');
                }
                $answer[$index]->{$sub_field} = $value;
            }
        }

        $answer = array_values(array_map(function ($image) {
            return "$image";
        }, $answer));

        return $answer;
    }

    public function render_admin_preview($answer)
    {
        $image = FieldImagesImage::from_string($answer);
        if (empty($image->image_id)) {
            return '';
        }
        return sprintf('' .
            '<div class="tuja-image tuja-image-existing">' .
            '<div class="tuja-image-preview">%s</div>' .
            '<div class="tuja-image-options">%s%s</div>' .
            '</div>',
            $this->render_image(uniqid(), $image->image_id),
            $this->get_option_label($image->option) ?: '',
            !empty($image->comment) ? sprintf('<br><em>"%s"</em>', $image->comment) : '');
    }


    private function get_option_label($option_key)
    {
        $matches = array_filter($this->options, function ($value) use ($option_key) {
            return $option_key == md5($value);
        });
        // Use reset() to return first element in array (array_filter preserves index so the first element may not be the 0th)
        return reset($matches);
    }


    public function render($field_name)
    {
        $hint = isset($this->hint) ? sprintf('<br><span class="tuja-question-hint">%s</span>', $this->hint) : '';
        return sprintf('<div class="tuja-field tuja-%s"><label>%s%s</label>%s%s</div>',
            strtolower((new \ReflectionClass($this))->getShortName()),
            $this->label,
            $hint,
            is_array($this->value) ? $this->render_images_list($field_name) : '',
            $this->render_image_upload("$field_name-NEW"));
    }

    private function render_images_list($field_name)
    {
		ob_start();
		foreach($this->value as $index => $val) {
			$image = FieldImagesImage::from_string($val);
			if (empty($image->image_id)) continue;

			?>
			<div class="tuja-image tuja-image-existing">
				<div class="tuja-image-preview">
					<?php echo $this->render_image("$field_name$index", $image->image_id); ?>
				</div>
				<div class="tuja-image-options">
					<?php echo $this->render_options_list("$field_name$index", $image->option); ?>
					<?php echo $this->render_comment_field("$field_name$index", $image->comment); ?>
					<?php if(!$this->read_only): ?>
						<div class="tuja-item-buttons">
							<button type="button" class="remove">Ta bort</button>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}

		return ob_get_clean();
    }

    private function render_options_list($field_name, $option_key)
    {
		$name = $field_name ?: $this->key;
		$disabled = $this->read_only ? ' disabled="disabled"' : '';

		ob_start();
		printf('<select name="%s-option"%s>', $name, $disabled);
		echo '<option value="">-- Välj --</option>';
		foreach($this->options as $keys => $value) {
			$selected = $option_key == md5($value) ? ' selected' : '';
			printf('<option value="%s"%s>%s</option>', md5($value), $selected, $value);
		}
		echo '</select>';

		return ob_get_clean();
    }

    private function render_comment_field($field_name, $comment)
    {
        return sprintf('<input type="text" name="%s-comment" value="%s" placeholder="%s" %s>',
            $field_name,
            $comment,
            $this->read_only ? '' : 'Kommentar',
            $this->read_only ? ' disabled="disabled"' : '');
    }

    private function render_image_upload($field_name)
    {
		wp_enqueue_script('tuja-dropzone');
		wp_enqueue_script('tuja-upload-script');

		ob_start();
		?>
		<div class="tuja-image tuja-image-new" id="<?php printf('%s-file', $field_name); ?>">
			<div class="tuja-image-select dropzone"></div>
			<div class="tuja-image-options">
				<label>Beskriv den nya bilden:</label>
				<?php
					echo $this->render_options_list($field_name, '');
					echo $this->render_comment_field($field_name, '');
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
    }

    private function render_image($field_name, $image_id)
    {
        $resized_image_url = $this->image_manager->get_resized_image_url($image_id, 200 * 200);
        return sprintf('%s<input type="hidden" name="%s-image_id" value="%s">',
            $resized_image_url ? sprintf('<img src="%s">', $resized_image_url) : 'Kan inte visa bild.',
            $field_name,
            $image_id);
    }

    private function get_uploaded_file_path($uploaded_file_info)
    {
        if ($uploaded_file_info['error'] > 0) {
            switch ($uploaded_file_info['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    throw new Exception('The uploaded file exceeds the limit of ' . ini_get('upload_max_filesize'));
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
                case UPLOAD_ERR_PARTIAL:
                    throw new Exception('The uploaded file was only partially uploaded.');
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception('Missing a temporary folder.');
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception('Failed to write file to disk.');
                case UPLOAD_ERR_EXTENSION:
                    throw new Exception('A PHP extension stopped the file upload.');
                default:
                    throw new Exception('Unknown problem occurred when uploading file.');
            }
        }
        return $uploaded_file_info['tmp_name'];
    }

    private function save_base64_file($base64_data)
    {
        $data = base64_decode($base64_data);
        if ($data !== false) {
            $temp_file_path = tempnam(sys_get_temp_dir(), 'tuja-base64image-');
            $handle = fopen($temp_file_path, "w");
            $write_result = fwrite($handle, $data);
            fclose($handle);
            if ($write_result !== false) {
                return $temp_file_path;
            }
        }
        throw new Exception('Could not save base64-encoded file.');
    }
}