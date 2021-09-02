<?php
namespace tuja\util\formattedtext;

use tuja\util\markdown\Parsedown;

/**
 * Regular Markdown parser but image URLs are WordPress media file ids instead.
 */
class FormattedText extends Parsedown {

	/**
	 * This Markdown will be replaced by an <img> element displaying media file 1337 in large size:
	 *   ![](1337)
	 */
	protected function inlineImage( $excerpt ) {
		$image = parent::inlineImage( $excerpt );

		if ( ! isset( $image ) ) {
			return null;
		}

		// Reference: https://developer.wordpress.org/reference/functions/wp_get_attachment_image_src/.
		$image_data = wp_get_attachment_image_src(
			intval( $image['element']['attributes']['src'] ),
			'large', // Any registered image size name, e.g. 'thumbnail'.
			false
		);

		if ( $image_data === false ) {
			return null;
		}

		list ($url, $width, $height, $is_resized) = $image_data;
		$image['element']['attributes']['src']    = $url;

		return $image;
	}
}
