<?php

namespace tuja;

use tuja\frontend\FrontendView;
use tuja\frontend\router\Controller;
use tuja\view\CountdownShortcode;
use tuja\view\CreateGroupShortcode;
use tuja\view\CreatePersonShortcode;
use tuja\view\EditGroupShortcode;
use tuja\view\EditPersonShortcode;
use tuja\view\GroupNameShortcode;
use WP_Query;

class Frontend extends Plugin {

	private static $scripts = [];
	private static $stylesheets = [];

	public static function use_script( string $value ) {
		self::$scripts[] = $value;
	}

	public static function use_stylesheet( string $value ) {
		self::$stylesheets[] = $value;
	}

	public function init() {
		add_shortcode( 'tuja_group_name', array( $this, 'group_name_shortcode' ) );
		add_shortcode( 'tuja_edit_group', array( $this, 'edit_group_shortcode' ) );
		add_shortcode( 'tuja_create_group', array( $this, 'create_group_shortcode' ) );
		add_shortcode( 'tuja_create_person', array( $this, 'create_person_shortcode' ) );
		add_shortcode( 'tuja_edit_person', array( $this, 'edit_person_shortcode' ) );
		add_shortcode( 'tuja_signup_opens_countdown', array( $this, 'signup_opens_countdown_shortcode' ) );
		add_shortcode( 'tuja_signup_closes_countdown', array( $this, 'signup_closes_countdown_shortcode' ) );
		add_shortcode( 'tuja_form_opens_countdown', array( $this, 'form_opens_countdown_shortcode' ) );
		add_shortcode( 'tuja_form_closes_countdown', array( $this, 'form_closes_countdown_shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );

		remove_filter( 'the_content', 'wpautop' ); // Don't let Wordpress add <p> tags to our HTML.

		$_POST = filter_var( $_POST, \FILTER_CALLBACK, [ 'options' => 'trim' ] );

		$this->init_page_controller();
	}

	private function init_page_controller() {
		$controller = new Controller();

		add_filter( 'do_parse_request', array( $controller, 'dispatch' ), PHP_INT_MAX, 2 );

		add_action( 'loop_end', function ( WP_Query $query ) {
			if ( isset( $query->virtual_page ) && ! empty( $query->virtual_page ) ) {
				$query->virtual_page = null;
			}
		} );

		add_filter( 'the_permalink', function ( $plink ) {
			global $post, $wp_query;
			if (
				$wp_query->is_page
				&& isset( $wp_query->virtual_page )
				&& $wp_query->virtual_page instanceof FrontendView
				&& isset( $post->is_virtual )
				&& $post->is_virtual
			) {
				$plink = home_url( $wp_query->virtual_page->getUrl() );
			}

			return $plink;
		} );
	}

	public function assets() {
		foreach ( self::$scripts as $script ) {
			if ( strpos( $script, 'http' ) === 0 ) {
				wp_enqueue_script( 'external-' . $script, $script );
			} elseif ( strpos( $script, 'tuja-' ) === 0 ) {
				wp_enqueue_script( 'tuja-' . $script, static::get_url() . 'assets/js/' . substr( $script, 5 ) );
			} else {
				wp_enqueue_script( $script );
			}
		}

		foreach ( self::$stylesheets as $stylesheet ) {
			if ( strpos( $stylesheet, 'http' ) === 0 ) {
				wp_enqueue_style( 'external-' . $stylesheet, $stylesheet );
			} elseif ( strpos( $stylesheet, 'tuja-' ) === 0 ) {
				wp_enqueue_style( 'tuja-' . $stylesheet, static::get_url() . 'assets/css/' . substr( $stylesheet, 5 ) );
			} else {
				wp_enqueue_style( $stylesheet );
			}
		}

		// TODO: What is this doing here?
		wp_register_script( 'tuja-countdown-script', static::get_url() . '/assets/js/countdown.js' );
	}

	public function group_name_shortcode( $atts ) {
		global $wp_query, $wpdb;
		$group_id  = $wp_query->query_vars['group_id'];
		$component = new GroupNameShortcode( $wpdb, $group_id );

		return $component->render();
	}

	public function edit_group_shortcode() {
		global $wp_query;
		$group_id = $wp_query->query_vars['group_id'];

		$component = new EditGroupShortcode( $group_id );

		return $component->render();
	}

	public function create_group_shortcode( $atts ) {
		$competition_id = $atts['competition'];

		$component = new CreateGroupShortcode( $competition_id );

		return $component->render();
	}

	public function create_person_shortcode( $atts ) {
		global $wp_query;
		$group_id  = $atts['group_id'] ?: $wp_query->query_vars['group_id'];
		$component = new CreatePersonShortcode( $group_id );

		return $component->render();
	}

	public function edit_person_shortcode( $atts ) {
		global $wp_query;
		$person_key = $wp_query->query_vars['group_id'];
		$component  = new EditPersonShortcode( $person_key );

		return $component->render();
	}

	public function signup_opens_countdown_shortcode( $atts ) {
		return CountdownShortcode::signup_opens( $atts );
	}

	public function signup_closes_countdown_shortcode( $atts ) {
		return CountdownShortcode::signup_closes( $atts );
	}

	public function form_opens_countdown_shortcode( $atts ) {
		return CountdownShortcode::submit_form_response_opens( $atts );
	}

	public function form_closes_countdown_shortcode( $atts ) {
		return CountdownShortcode::submit_form_response_closes( $atts );
	}

	private static function bool_attr( $attributes, $attr_name ): bool {
		return isset( $attributes[ $attr_name ] ) && in_array( strtolower( $attributes[ $attr_name ] ), [
				'yes',
				'true'
			] );
	}
}

new Frontend();
