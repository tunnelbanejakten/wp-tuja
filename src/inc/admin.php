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
		add_action('admin_menu', array($this, 'add_admin_menu_item'));
		add_action('admin_enqueue_scripts', array($this, 'assets'));
	}

	public function add_admin_menu_item() {
		add_menu_page( 'Tunnelbanejakten', 'Tunnelbanejakten', 'manage_options', static::SLUG, array($this, 'route') );
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
			$view = 'tuja\admin\\' . sanitize_text_field( $_GET['tuja_view'] );
			if ( class_exists( $view ) ) {
				$view = new $view();


				if ( method_exists( $view, 'output' ) ) {
					$view->output();
				}
			}
		}
	}
}

new Admin();
