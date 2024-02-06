<?php

namespace tuja;

use tuja\admin\AdminUtils;
use tuja\controller\SearchController;
use tuja\data\model\UploadId;
use tuja\data\store\CompetitionDao;
use tuja\data\store\UploadDao;
use tuja\util\Strings;
use tuja\util\TemplateEditor;

class Admin extends Plugin {

	static private $notices = array();


	function add_thickbox() {
		add_thickbox();
	}

	public function init() {
		add_action( 'init', array( $this, 'handle_post' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu_item' ) );
		add_action( 'admin_notices', array( $this, 'print_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_thickbox' ) );
		add_action( 'admin_action_tuja_report', array( $this, 'render_report' ) );
		add_action( 'admin_action_tuja_questions_preview', array( $this, 'render_questions_preview' ) );
		add_action( 'admin_action_tuja_markdown', array( $this, 'render_markdown' ) );
		add_action( 'admin_action_tuja_search', array( $this, 'search' ) );
		add_action( 'admin_action_tuja_favourite_upload', array( $this, 'favourite_upload' ) );

		Strings::init( intval( @$_GET['tuja_competition'] ?: 0 ) );
	}

	public function print_notices() {
		AdminUtils::enableNoticePrint();
	}

	public function handle_post() {
		$class_name = ! empty( $_POST['tuja_action'] ) && ! empty( $_GET['tuja_view'] ) ? 'tuja\\admin\\' . $_GET['tuja_view'] : null;

		if ( $class_name && class_exists( $class_name ) ) {
			$interfaces = class_implements( $class_name ) ?: array();

			if ( in_array( 'tuja\\util\\RouterInterface', $interfaces ) ) {
				( new $class_name() )->handle_post();
			}
		}
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

	function render_questions_preview() {
		define( 'IFRAME_REQUEST', true );
		iframe_header();

		$this->route_questions_preview();

		iframe_footer();
		exit;
	}

	function render_markdown() {
		define( 'IFRAME_REQUEST', true );

		print TemplateEditor::render_preview( $_POST['__template'], $_POST );

		exit;
	}

	// TODO: There must be a better, a more high-level, way to define a JSON REST endpoint...
	function search() {
		define( 'IFRAME_REQUEST', true );
		header( 'Content-type: application/json' );

		$competition_dao = new CompetitionDao();

		$competition = null;
		if ( isset( $_GET['tuja_competition'] ) ) {
			$competition = $competition_dao->get( $_GET['tuja_competition'] );
		}

		if ( ! $competition ) {
			print 'Could not find competition';

			exit;
		}
		$controller = new SearchController( $competition );

		$result = $controller->search( $_GET['query'] );

		print json_encode( $result );
		exit;
	}

	function favourite_upload() {
		define( 'IFRAME_REQUEST', true );
		header( 'Content-type: application/json' );

		$upload_dao    = new UploadDao();
		$affected_rows = $upload_dao->update_favourite_status(
			UploadId::from_string( $_GET['tuja_upload_id'] ),
			$_POST['is_favourite'] === 'true'
		);

		print json_encode( array( 'result' => 1 === $affected_rows ) );
		exit;
	}


	public function add_admin_menu_item() {
		add_menu_page(
			'Tunnelbanejakten',
			'Tunnelbanejakten',
			'edit_pages',
			static::SLUG,
			array(
				$this,
				'route',
			)
		);
	}

	public function assets() {
		if ( @$_REQUEST['action'] === 'tuja_report' ) {
			wp_enqueue_style( 'tuja-admin-report', static::get_url() . '/assets/css/admin-report.css' );
		} elseif ( @$_REQUEST['action'] === 'tuja_questions_preview' ) {
			wp_enqueue_style( 'tuja-default', static::get_url() . '/assets/css/wp.css' );
			wp_enqueue_style( 'tuja-admin-questions-preview', static::get_url() . '/assets/css/admin-questions-preview.css' );
		} else {
			wp_enqueue_style( 'tuja-admin-theme', static::get_url() . '/assets/css/admin.css' );
			wp_enqueue_style( 'tuja-admin-templateeditor', static::get_url() . '/assets/css/admin-templateeditor.css' );
			wp_enqueue_style( 'tuja-admin-jsoneditor', static::get_url() . '/assets/css/admin-jsoneditor.css' );
			wp_enqueue_style( 'tuja-admin-breadcrumbsmenu', static::get_url() . '/assets/css/admin-breadcrumbsmenu.css' );
			wp_enqueue_style( 'tuja-admin-search', static::get_url() . '/assets/css/admin-search.css' ); // TODO: Include this conditionally
			wp_enqueue_style( 'tuja-admin-map', static::get_url() . '/assets/css/admin-map.css' ); // TODO: Include this conditionally
			wp_enqueue_style( 'tuja-admin-leaflet', static::get_url() . '/assets/css/leaflet-1.8.0.css' ); // TODO: Include this conditionally
		}

		// Load scripts based on screen->id
		$screen = get_current_screen();

		if ( ($screen->id === 'toplevel_page_tuja' || $screen->id === 'admin') && isset( $_GET['tuja_view'] ) ) {
			foreach ( $this->list_scripts( $_GET['tuja_view'] ) as $script_file_name ) {
				wp_enqueue_script( 'tuja-script-' . $script_file_name, static::get_url() . '/assets/js/' . $script_file_name );
			}
		}
	}

	public function route() {
		$this->render_view( @$_GET['tuja_view'] ?: 'Competitions' );
	}

	public function route_report() {
		$this->render_view( @$_GET['tuja_view'] );
	}

	public function route_questions_preview() {
		$this->render_view( 'FormQuestionsPreview' );
	}

	private function render_view( $view_name ) {
		$possible_class_names = array(
			'tuja\\admin\\' . sanitize_text_field( $view_name ),
			'tuja\\admin\\reportgenerators\\' . sanitize_text_field( $view_name ),
		);
		foreach ( $possible_class_names as $view ) {
			if ( class_exists( $view ) ) {
				$view = new $view();
				if ( method_exists( $view, 'output' ) ) {
					$view->output();
					return;
				}
			}
		}

		AdminUtils::printError( 'View not found.' );
	}

	private function list_scripts( $view_name ) {
		$possible_class_names = array(
			'tuja\\admin\\' . sanitize_text_field( $view_name ),
			'tuja\\admin\\reportgenerators\\' . sanitize_text_field( $view_name ),
		);
		foreach ( $possible_class_names as $view ) {
			if ( class_exists( $view ) ) {
				$view = new $view();

				if ( method_exists( $view, 'get_scripts' ) ) {
					return $view->get_scripts();
				}
			}
		}

		return array();
	}
}

new Admin();
