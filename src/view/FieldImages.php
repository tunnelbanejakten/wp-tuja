<?php

namespace tuja\view;

use Exception;
use util\ImageManager;

include_once 'Field.php';

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
        if ($_FILES["$form_field-NEW-file"]) {
            // TODO: Move handling of uploaded image to other method. A "get method" should not have side-effects like this.
            // Process upload of new image
            $uploaded_file_info = $_FILES["$form_field-NEW-file"];
            if (isset($uploaded_file_info)) {
                if ($uploaded_file_info['error'] > 0) {
                    switch ($uploaded_file_info['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                            printf('<p>Error: %s</p>', 'The uploaded file exceeds the limit of ' . ini_get('upload_max_filesize'));
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            printf('<p>Error: %s</p>', 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            printf('<p>Error: %s</p>', 'The uploaded file was only partially uploaded.');
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            printf('<p>Error: %s</p>', 'No file was uploaded.');
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            printf('<p>Error: %s</p>', 'Missing a temporary folder.');
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            printf('<p>Error: %s</p>', 'Failed to write file to disk.');
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            printf('<p>Error: %s</p>', 'A PHP extension stopped the file upload.');
                            break;
                    }
                } else {
                    try {
                        $import_result = $this->image_manager->import_jpeg($uploaded_file_info['tmp_name']);
                        if ($import_result) {
                            $answer['NEW'] = [
                                'image_id' => $import_result,
                                'option' => $_POST["$form_field-NEW-option"],
                                'comment' => $_POST["$form_field-NEW-comment"]
                            ];
                        }
                    } catch (Exception $e) {
                        // TODO: Bad error handling here.
                        printf('<p>Image import failed. %s</p> ', $e->getMessage());
                    }
                }
            }

        }

        foreach ($_POST as $key => $value) {
            if (substr($key, 0, strlen($form_field)) === $form_field) {
                list($index, $sub_field) = explode('-', substr($key, strlen($form_field)), 2);
                $answer[$index][$sub_field] = $value;
            }
        }

        $answer = array_values(array_map(function ($sub_fields) {
            // TODO: Extract sub-field property names to constants.
            return join(',', array($sub_fields['image_id'], $sub_fields['option'], $sub_fields['comment']));
        }, $answer));

        return $answer;
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
        return join(array_map(function ($index, $val) use ($field_name) {
            // TODO: Small DTO class to handle comma-separated answer?
            list ($image_id, $option_key, $comment) = explode(',', $val, 3);
            if (empty($image_id)) {
                return '';
            }
            return sprintf('<div class="tuja-image tuja-image-existing"><div class="tuja-image-preview">%s</div><div class="tuja-image-options">%s%s%s</div></div>',
                $this->render_image("$field_name$index", $image_id),
                $this->render_options_list("$field_name$index", $option_key),
                $this->render_comment_field("$field_name$index", $comment),
                // TODO: Should removing an image be handled in the same way as removing a group member? The former uses Javascript, the latter regular submit action.
                sprintf('<div class="tuja-item-buttons"><button type="button" onclick="this.parentNode.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode.parentNode)">Ta bort</button></div>'));
        }, array_keys($this->value), array_values($this->value)));
    }

    private function render_options_list($field_name, $option_key)
    {
        return join(array_map(function ($index, $value) use ($field_name, $option_key) {
            $id = $field_name . '-' . $index;
            $name = $field_name ?: $this->key;
            return sprintf('<div class="tuja-%s-radiobutton"><input type="radio" name="%s-option" value="%s" class="tuja-%s tuja-%s-shortlist" id="%s" %s/><label for="%s">%s</label></div>',
                strtolower((new \ReflectionClass($this))->getShortName()),
                $name,
                md5($value),
                strtolower((new \ReflectionClass($this))->getShortName()),
                strtolower((new \ReflectionClass($this))->getShortName()),
                $id,
                $option_key == md5($value) ? ' checked="checked"' : '',
                $id,
                htmlspecialchars($value));
        }, array_keys($this->options), array_values($this->options)));
    }

    private function render_comment_field($field_name, $comment)
    {
        return sprintf('<input type="text" name="%s-comment" value="%s" placeholder="Kommentar">', $field_name, $comment);
    }

    private function render_file_upload_field($field_name)
    {
        return sprintf('<input type="file" name="%s-file">', $field_name);
    }

    private function render_image_upload($field_name)
    {
        return sprintf('<div class="tuja-image tuja-image-new"><div class="tuja-image-select">Välj bild att ladda upp (laddas upp när du trycker på Uppdatera svar):<br>%s</div><div class="tuja-image-options">%s%s</div></div>',
            $this->render_file_upload_field($field_name),
            $this->render_options_list($field_name, ''),
            $this->render_comment_field($field_name, ''));
    }

    private function render_image($field_name, $image_id)
    {
        $resized_image_url = $this->image_manager->get_resized_image_url($image_id, 200 * 200);
        if (!$resized_image_url) {
            // TODO: The input fields are identical in both cases. DRY.
            return sprintf('%s<input type="hidden" name="%s-image_id" value="%s">', 'Kan inte visa bild.', $field_name, $image_id);
        }
        return sprintf('<img src="%s"><input type="hidden" name="%s-image_id" value="%s">', $resized_image_url, $field_name, $image_id);
    }
}