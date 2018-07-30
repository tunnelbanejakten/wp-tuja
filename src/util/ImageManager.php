<?php

namespace util;

use Exception;

class ImageManager
{


    public function __construct()
    {
        $dir = wp_upload_dir('tuja', true, false);
        if (!isset($dir['path'])) {
            throw new Exception('Could not find folder to put image in.');
        }
        $this->directory = trailingslashit($dir['path']);
        $this->public_url_directory = trailingslashit($dir['url']);
    }

    public function import_jpeg($file_path): string
    {
        if (file_exists($file_path)) {
            // TODO: Use exif_imagetype if enabled
//            function_exists()
            $is_valid_jpeg = /*(exif_imagetype($file_path) == IMAGETYPE_JPEG) && */
                (@imagecreatefromjpeg($file_path) !== false);
            if (!$is_valid_jpeg) {
                throw new Exception('Not valid JPEG image.');
            }

            $md5 = md5_file($file_path);
            $new_path = $this->directory . $md5 . '.jpg';

            if (file_exists($new_path)) {
                // This exact file has been uploaded before.
                return $md5;
            }

            if ($this->move($file_path, $new_path)) {
                return $md5;
            } else {
                throw new Exception(sprintf('Could not save uploaded file to %s', $new_path));
            }

        } else {
            throw new Exception(sprintf('File %s not found', $file_path));
        }
    }

    public function get_resized_image_url($file_id, $pixels)
    {
        $dst_filename = "$file_id-$pixels.jpg";
        $dst_path = $this->directory . $dst_filename;
        if (file_exists($dst_path)) {
            return $this->public_url_directory . $dst_filename;
        }
        $src_path = $this->directory . "$file_id.jpg";
        $src_image = @imagecreatefromjpeg($src_path);
        if ($src_image !== false) {
            list($width, $height) = getimagesize($src_path);
            $dst_width = sqrt(($pixels * $width) / $height);
            $dst_height = $dst_width * ($height / $width);
            $dst_image = imagecreatetruecolor($dst_width, $dst_height);
            if (@imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $dst_width, $dst_height, $width, $height)) {
                if (@imagejpeg($dst_image, $dst_path)) {
                    return $this->public_url_directory . $dst_filename;
                }
            }
        }
        return false;
    }

    public function get_public_url($file_md5_hash): string
    {
        return $this->public_url_directory . $file_md5_hash . '.jpg';
    }

    private function move($old, $new)
    {
        if (is_uploaded_file($old)) {
            return move_uploaded_file($old, $new);
        } else {
            return rename($old, $new);
        }
    }
}