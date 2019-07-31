<?php

namespace tuja;

use tuja\data\store\CompetitionDao;
use tuja\data\model\Competition;

class Admin extends Plugin {

	static private $notices = array();

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu_item' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_action_tuja_report', array( $this, 'render_report' ) );
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


	public function add_admin_menu_item() {
		add_menu_page( 'Tunnelbanejakten', 'Tunnelbanejakten', 'edit_pages', static::SLUG, array($this, 'route') );
	}

	public function assets() {
		if ( $_REQUEST['action'] === 'tuja_report' ) {
			wp_enqueue_style( 'tuja-admin-report', static::get_url() . '/assets/css/admin-report.css' );
		} else {
			wp_enqueue_style( 'tuja-admin-theme', static::get_url() . '/assets/css/admin.css' );
		}

		// Load scripts based on screen->id
		$screen = get_current_screen();

		if ( $screen->id === 'toplevel_page_tuja' ) {
			wp_enqueue_script( 'tuja-admin-competition-settings', static::get_url() . '/assets/js/admin-competition-settings.js' );
			wp_enqueue_script( 'tuja-admin-message-send', static::get_url() . '/assets/js/admin-message-send.js' );
			wp_enqueue_script( 'tuja-admin-review-component', static::get_url() . '/assets/js/admin-review-component.js' );
		}
	}

	public function route() {
		if(empty($_GET['tuja_view'])) {
			$db_competition = new CompetitionDao();

			if(isset($_POST['tuja_action']) && ($_POST['tuja_action'] === 'competition_create')) {
				$props = new Competition();
				$props->name = $_POST['tuja_competition_name'];
				$db_competition->create( $props );
			}

			include( Plugin::PATH . '/admin/views/index.php' );
		} else {
			$this->render_view( $_GET['tuja_view'] );
		}
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
}

new Admin();
