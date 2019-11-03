<?php

namespace tuja\frontend;

use WP_Post;

/**
 * Credits: https://gist.github.com/gmazzap/1efe17a8cb573e19c086
 */
abstract class FrontendPage {

	private $wp_post = null;

	private $url;
	private $wp_template;

	function __construct(
		$url,
		$wp_template = 'page.php'
	) {
		$this->url         = filter_var( $url, FILTER_SANITIZE_URL );
		$this->wp_template = $wp_template;
	}

	abstract function render();

	abstract function get_title();

	function get_content() {
		ob_start();

		$this->render();

		return ob_get_clean();
	}

	function as_wp_post() {
		if ( is_null( $this->wp_post ) ) {
			$post          = array(
				'ID'             => 0,
				'post_title'     => $this->get_title(),
				'post_name'      => sanitize_title( $this->get_title() ),
				'post_content'   => $this->get_content() ?: '',
				'post_excerpt'   => '',
				'post_parent'    => 0,
				'menu_order'     => 0,
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'comment_count'  => 0,
				'post_password'  => '',
				'to_ping'        => '',
				'pinged'         => '',
				'guid'           => home_url( $this->url ),
				'post_date'      => current_time( 'mysql' ),
				'post_date_gmt'  => current_time( 'mysql', 1 ),
				'post_author'    => 0, // is_user_logged_in() ? get_current_user_id() : 0,
				'is_virtual'     => true,
				'filter'         => 'raw'
			);
			$this->wp_post = new WP_Post( (object) $post );
		}

		return $this->wp_post;
	}

	public function get_wp_template(): string {
		return $this->wp_template;
	}

}