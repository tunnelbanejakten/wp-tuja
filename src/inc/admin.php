<?php

namespace tuja;

use tuja\data\store\CompetitionDao;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\ResponseDao;
use tuja\data\store\PointsDao;
use tuja\data\store\MessageDao;
use tuja\data\model\Competition;

class Admin extends Plugin {

	static private $notices = array();

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu_item' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_notices', array( $this, 'notices_html' ) );
	}

	static public function notice( $message, $type = 'info' ) {
		$message = sanitize_text_field( $message );
		$type    = sanitize_text_field( $type );

		if ( ! in_array( $type, array( 'info', 'warning', 'error', 'success' ), true ) ) {
			return;
		}

		self::$notices[] = array(
			'type'    => $type,
			'message' => $message
		);
	}

	public function route() {
		if ( empty( $_GET['tuja_view'] ) ) {
			return;
		}

		$view = 'tuja\admin\\' . sanitize_text_field( $_GET['tuja_view'] );
		if ( class_exists( $view ) ) {
			$view = new $view();

			if ( method_exists( $view, 'output' ) ) {
				$view->output();
			}
		}
	}

	public function notices_html() {
		if ( ! empty( self::$notices ) ) {
			foreach ( self::$notices as $notice ) {
				printf( '<div class="%1$s"><p>%2$s</p></div>', 'notice notice-' . $notice['type'] . ' is-dismissable', $notice['message'] );
			}
		}
	}

	public function add_admin_menu_item() {
		add_menu_page( 'Tunnelbanejakten', 'Tunnelbanejakten', 'manage_options', static::SLUG, array(
			$this,
			'show_admin_page'
		) );
	}

	public function assets() {
		wp_enqueue_style( 'tuja-admin-theme', static::get_url() . '/assets/css/admin.css' );

		// Load scripts based on screen->id
		$screen = get_current_screen();

		if ( $screen->id === 'toplevel_page_tuja' ) {
			wp_enqueue_script( 'tuja-admin-competition-settings', static::get_url() . '/assets/js/admin-competition-settings.js' );
			wp_enqueue_script( 'tuja-admin-message-send', static::get_url() . '/assets/js/admin-message-send.js' );
		}
	}

	public function show_admin_page() {
		global $wpdb;

		// https://wpartisan.me/tutorials/wordpress-auto-adds-slashes-post-get-request-cookie
		$_POST    = array_map( 'stripslashes_deep', $_POST );
		$_GET     = array_map( 'stripslashes_deep', $_GET );
		$_COOKIE  = array_map( 'stripslashes_deep', $_COOKIE );
		$_REQUEST = array_map( 'stripslashes_deep', $_REQUEST );

		// TODO: Create DAOs on-demand, not all-at-once here
		$db_competition = new CompetitionDao();
		$db_form        = new FormDao();
		$db_groups      = new GroupDao();
		$db_question    = new QuestionDao();
		$db_response    = new ResponseDao();
		$db_points      = new PointsDao();
		$db_message     = new MessageDao();

		if ( $_POST['tuja_action'] === 'competition_create' ) {
			$props       = new Competition();
			$props->name = $_POST['tuja_competition_name'];
			$db_competition->create( $props );
		}

		if ( isset( $_GET['tuja_view'] ) ) {
			$this->route();
		} else {
			$view = 'index';
			printf( '<div class="tuja tuja-view-%s">', $view );
			include( Plugin::PATH . '/admin/views/' . $view . '.php' );
			print( '</div>' );
		}

	}
}

new Admin();
