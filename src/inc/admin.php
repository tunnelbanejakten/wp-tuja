<?php
	namespace tuja;
	
	class Admin extends Plugin {

		static private $notices = array();
		
		protected function init() {
			add_action('admin_menu', array($this, 'add_admin_menu_item'));
			add_action('admin_enqueue_scripts', array($this, 'assets'));
			add_action('init', array($this, 'handle_post'));
			add_action('admin_notices', array($this, 'notices_html'));
		}

		static public function notice($message, $type = 'info') {
			$message = sanitize_text_field( $message );
			$type = sanitize_text_field( $type );

			if(!in_array($type, array('info', 'warning', 'error', 'success'), true)) return;

			self::$notices[] = array(
				'type' => $type,
				'message' => $message
			);
		}

		public function notices_html() {
			if(!empty(self::$notices)) {
				foreach(self::$notices as $notice) {
					printf('<div class="%1$s"><p>%2$s</p></div>', 'notice notice-' . $notice['type'] . ' is-dismissable', $notice['message']); 
				}
			}
		}
				
		public function add_admin_menu_item() {
			add_menu_page('Tunnelbanejakten', 'Tunnelbanejakten', 'manage_options', static::SLUG, array($this, 'show_admin_page'));
		}

		public function assets() {
			wp_enqueue_style('tuja-admin-theme', static::get_url() . '/assets/css/admin.css');
			
			// Load scripts based on screen->id
			// $screen = get_current_screen();
		}

		public function show_admin_page() {
			global $wpdb;
			// TODO: Create DAOs on-demand, not all-at-once here.
			$db_competition = new CompetitionDao($wpdb);
			$db_form = new FormDao($wpdb);
			$db_groups = new GroupDao($wpdb);
			$db_question = new QuestionDao($wpdb);
			$db_response = new ResponseDao($wpdb);
			$db_points = new PointsDao($wpdb);
			$db_message = new MessageDao($wpdb);

			if($_POST['tuja_action'] === 'competition_create') {
				$props = new Competition();
				$props->name = $_POST['tuja_competition_name'];
				$db_competition->create($props);
			}

			$view = $_GET['tuja_view'] ?: 'index';

			printf('<div class="tuja tuja-view-%s">', $view);
			include('admin/' . $view . '.php');
			print('</div>');
		}
	}
	
	new Admin();
