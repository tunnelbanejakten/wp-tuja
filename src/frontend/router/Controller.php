<?php

namespace tuja\frontend\router;

use tuja\frontend\FrontendView;
use WP;
use WP_Post;

/**
 * Credits: https://gist.github.com/gmazzap/1efe17a8cb573e19c086
 */
class Controller {

	private $view_initiators = [];
	private $loader;

	function __construct() {
		$this->loader = new TemplateLoader;

		$this->view_initiators = [
			new GroupSignupInitiator(),
			new GroupPeopleEditorInitiator(),
			new GroupEditorInitiator(),
			new CompetitionSignupInitiator(),
			new PersonEditorInitiator(),
			new GroupHomeInitiator(),
			new GroupTicketsInitiator(),
			new FormInitiator(),
			new PointsOverrideInitiator(),
			new ReportPointsInitiator(),
			new GroupStatusInitiator(),
			new GroupCancelSignupInitiator(),
			new GroupCheckinInitiator(),
			new GroupPaymentInitiator(),
			new SlideshowInitiator(),
			new ExpenseReportInitiator(),
		];
	}

	function dispatch( $bool, WP $wp ) {
		$matched = $this->check_request();
		if ( $matched ) {
			$this->loader->init( $matched );
			$wp->virtual_page = $matched;
			do_action( 'parse_request', $wp );
			$this->init_wp_query( $matched );
			do_action( 'wp', $wp );
			$this->loader->load();
			$this->handle_exit();
		}

		return $bool;
	}

	private function check_request() {
		$path = trim( $this->get_path_info(), '/' );
		$path = preg_replace( '/\?.*/', '', $path ); // Strip query string when looking for matching view
		foreach ( $this->view_initiators as $page ) {
			if ( $page->is_handler( $path ) ) {
				return $page->create_page( $path );
			}
		}
		return null;
	}

	private function get_path_info() {
		$home_path = parse_url( home_url(), PHP_URL_PATH );

		return preg_replace( "#^/?{$home_path}/#", '/', add_query_arg( array() ) );
	}

	private function init_wp_query( FrontendView $page ) {
		global $wp_query;
		$wp_query->init();
		$wp_query->is_page        = true;
		$wp_query->is_singular    = true;
		$wp_query->is_home        = false;
		$wp_query->found_posts    = 1;
		$wp_query->post_count     = 1;
		$wp_query->max_num_pages  = 1;
		$posts                    = (array) apply_filters(
			'the_posts', array( $page->as_wp_post() ), $wp_query
		);
		$post                     = $posts[0];
		$wp_query->posts          = $posts;
		$wp_query->post           = $post;
		$wp_query->queried_object = $post;
		$GLOBALS['post']          = $post;
		$wp_query->virtual_page   = $post instanceof WP_Post && isset( $post->is_virtual )
			? $page
			: null;
	}

	public function handle_exit() {
		exit();
	}
}