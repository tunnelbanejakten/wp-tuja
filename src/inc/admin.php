<?php

namespace tuja;

use tuja\admin\AdminUtils;
use tuja\util\Strings;
use tuja\util\TemplateEditor;

class Admin extends Plugin {

	static private $notices = array();

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu_item' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_action_tuja_report', array( $this, 'render_report' ) );
		add_action( 'admin_action_tuja_markdown', array( $this, 'render_markdown' ) );

		add_thickbox();

		Strings::init( intval( @$_GET['tuja_competition'] ?: 0 ) );
	}

	function render_report() {
		define( 'IFRAME_REQUEST', true );
		if ( $_GET['tuja_report_format'] != 'csv' ) {
			iframe_header();
		}

		$this->route_report( $_GET['tuja_view'] );

		if ( $_GET['tuja_report_format'] != 'csv' ) {
			iframe_footer();
		}
		exit;
	}

	function render_markdown() {
		define( 'IFRAME_REQUEST', true );

		print TemplateEditor::render_preview( $_POST['__template'], $_POST );

		exit;
	}


	public function add_admin_menu_item() {
		add_menu_page( 'Tunnelbanejakten', 'Tunnelbanejakten', 'edit_pages', static::SLUG, array(
			$this,
			'route_support'
		) );
		add_submenu_page( self::SLUG, 'För kundtjänst', 'För kundtjänst', 'edit_pages', static::SLUG, array(
			$this,
			'route_support'
		) );
		add_submenu_page( self::SLUG, 'För admin', 'För admin', 'edit_pages', static::SLUG . '_admin', array(
			$this,
			'route_admin'
		) );
	}

	public function assets() {
		if ( @$_REQUEST['action'] === 'tuja_report' ) {
			wp_enqueue_style( 'tuja-admin-report', static::get_url() . '/assets/css/admin-report.css' );
		} else {
			wp_enqueue_style( 'tuja-admin-theme', static::get_url() . '/assets/css/admin.css' );
			wp_enqueue_style( 'tuja-admin-templateeditor', static::get_url() . '/assets/css/admin-templateeditor.css' );
		}

		// Load scripts based on screen->id
		$screen = get_current_screen();

		if ( $screen->id === 'toplevel_page_tuja' || $screen->id === 'tunnelbanejakten_page_tuja_admin' ) {
			foreach ( $this->list_scripts( $_GET['tuja_view'] ) as $script_file_name ) {
				wp_enqueue_script( 'tuja-script-' . $script_file_name, static::get_url() . '/assets/js/' . $script_file_name );
			}
		}
	}

	public function route_support() {
		$this->route( false );
	}

	public function route_admin() {
		$this->route( true );
	}

	public function route( $is_admin ) {
		AdminUtils::set_admin_mode( $is_admin );

		$this->render_view( $_GET['tuja_view'] ?: 'Competitions' );
	}

	public function route_report() {
		$this->render_view( $_GET['tuja_view'] );
	}

	private function render_view( $view_name ) {
		$view = 'tuja\\admin\\' . sanitize_text_field( $view_name );
		if ( class_exists( $view ) ) {
			$view = new $view();

			if ( method_exists( $view, 'output' ) ) {
				$view->output();
			}
		}
	}

	private function list_scripts( $view_name ) {
		$view = 'tuja\\admin\\' . sanitize_text_field( $view_name );
		if ( class_exists( $view ) ) {
			$view = new $view();

			if ( method_exists( $view, 'get_scripts' ) ) {
				return $view->get_scripts();
			}
		}

		return [];
	}
}

new Admin();
