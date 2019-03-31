<?php

namespace tuja\view;

use Exception;
use tuja\util\ImageManager;

class FieldImagesImage
{
    public $images;
    public $option;
    public $comment;

    public function __construct($images, $option, $comment)
    {
        $this->images = $images;
        $this->option = $option;
        $this->comment = $comment;
    }

    public function __toString()
    {
		$this->to_string();
	}
	
	public function to_string() {
		return json_encode(array(
			'images' => $this->images,
			'option' => $this->option,
			'comment' => $this->comment
		));
	}

    public static function from_string($answer): FieldImagesImage
    {
		$answer = json_decode($answer, true);
        return new FieldImagesImage($answer['images'], $answer['option'], $answer['comment']);
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
		$answer = array();
		if(isset($_POST[$form_field])) {
			$data = sanitize_post($_POST[$form_field]);
			$answer = new FieldImagesImage($data['images'], $data['option'], $data['comment']);
			$answer = $answer->to_string();
		}

        return array($answer);
    }

    public function render_admin_preview($answer)
    {
        $image = FieldImagesImage::from_string($answer);
        if (empty($image->images)) {
            return '';
        }
        return '<div class="tuja-image tuja-image-existing">Under konstruktion</div>';
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
        return sprintf('<div class="tuja-field tuja-%s"><label>%s%s</label>%s</div>',
            strtolower((new \ReflectionClass($this))->getShortName()),
            $this->label,
            $hint,
            $this->render_image_upload($field_name));
    }


    private function render_options_list($field_name, $option_key)
    {
		$name = $field_name ?: $this->key;
		$disabled = $this->read_only ? ' disabled="disabled"' : '';

		ob_start();
		printf('<select name="%s[option]"%s>', $name, $disabled);
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
        return sprintf('<input type="text" name="%s[comment]" value="%s" placeholder="%s" %s>',
            $field_name,
            $comment,
            $this->read_only ? '' : 'Kommentar',
            $this->read_only ? ' disabled="disabled"' : '');
    }

    private function render_image_upload($field_name)
    {
		wp_enqueue_script('tuja-dropzone');
		wp_enqueue_script('tuja-upload-script');

		$images = array();
		if($this->value) {
			$image = FieldImagesImage::from_string($this->value[0]);
			if(!empty($image->images)) {
				foreach($image->images as $filename) {
					$images[] = sprintf('<input type="hidden" name="%s[images][]" value="%s">', $field_name, $filename);
				}
			}
		}

		if(empty($images)) {
			$images[] = sprintf('<input type="hidden" name="%s[images][]" value="">', $field_name);
		}

		ob_start();
		?>
		<div class="tuja-image" id="<?php echo $field_name; ?>">
			<div class="tuja-image-select dropzone"></div>
			<?php echo implode('', $images); ?>
			<div class="tuja-image-options">
				<label>Välj bildgrupp:</label>
				<?php
					echo $this->render_options_list($field_name, isset($image) ? $image->option : '');
					echo $this->render_comment_field($field_name, isset($image) ? $image->comment : '');
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
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