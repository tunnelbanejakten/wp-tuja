<?php
	namespace Webbmaffian;
	
	class Frontend extends Plugin {
		
		protected function init() {
			$this->shortcodes();
		}

		public function shortcodes() {
			add_shortcode('tuja_form', array($this, 'form_shortcode'));
			add_shortcode('tuja_points', array($this, 'points_shortcode'));
			add_shortcode('tuja_group_name', array($this, 'group_name_shortcode'));
			add_shortcode('tuja_edit_group', array($this, 'edit_group_shortcode'));
			add_shortcode('tuja_create_group', array($this, 'create_group_shortcode'));
			add_shortcode('tuja_create_person', array($this, 'create_person_shortcode'));
			add_shortcode('tuja_edit_person', array($this, 'edit_person_shortcode'));
			add_shortcode('tuja_signup_opens_countdown', array($this, 'signup_opens_countdown_shortcode'));
			add_shortcode('tuja_signup_closes_countdown', array($this, 'signup_closes_countdown_shortcode'));
			add_shortcode('tuja_form_opens_countdown', array($this, 'form_opens_countdown_shortcode'));
			add_shortcode('tuja_form_closes_countdown', array($this, 'form_closes_countdown_shortcode'));

			add_filter('query_vars', array($this, 'query_vars'));
			add_filter('rewrite_rules_array', array($this, 'rewrite_rules'));

			add_action('wp_enqueue_scripts', array($this, 'assets'));
		}

		public function assets() {
			wp_register_script('tuja-recaptcha-script', 'https://www.google.com/recaptcha/api.js');
			wp_register_script('tuja-upload-script', static::get_url() . '/assets/js/upload.js');
			wp_register_script('tuja-countdown-script', static::get_url() . '/assets/js/countdown.js');
			wp_register_script('tuja-editgroup-script', static::get_url() . '/assets/js/edit-group.js');

			wp_enqueue_style('tuja-wp-theme', static::get_url() . '/assets/css/wp.css');
		}

		public function form_shortcode($atts) {
			global $wp_query, $wpdb;
			$form_id = $atts['form'];
			$is_readonly = in_array(strtolower($atts['readonly']), ['yes', 'true']);
			$group_id = $wp_query->query_vars['group_id'];
			$component = $is_readonly ?
				new FormReadonlyShortcode($wpdb, $form_id, $group_id) :
				new FormShortcode($wpdb, $form_id, $group_id);
			return $component->render();
		}

		public function points_shortcode($atts) {
			global $wp_query, $wpdb;
			$competition_id = $atts['competition'];
			$group_id = $wp_query->query_vars['group_id'];
			$component = new PointsShortcode($wpdb, $competition_id, $group_id);
			return $component->render();
		}

		public function group_name_shortcode($atts) {
			global $wp_query, $wpdb;
			$group_id = $wp_query->query_vars['group_id'];
			$component = new GroupNameShortcode($wpdb, $group_id);
			return $component->render();
		}

		public function edit_group_shortcode($atts) {
			global $wp_query, $wpdb;
			$group_id = $wp_query->query_vars['group_id'];
			$is_crew_form = $atts['is_crew_form'] === 'yes';
			$component = new EditGroupShortcode($wpdb, $group_id, $is_crew_form);
			return $component->render();
		}

		public function create_group_shortcode($atts) {
			global $wpdb;
			$competition_id = $atts['competition'];
			$edit_link_template = $atts['edit_link_template'];
			$is_crew_form = $atts['is_crew_form'] === 'yes';
			$component = new CreateGroupShortcode($wpdb, $competition_id, $edit_link_template, $is_crew_form);
			return $component->render();
		}

		public function create_person_shortcode($atts) {
			global $wp_query, $wpdb;
			$group_id = $wp_query->query_vars['group_id'];
			$edit_link_template = $atts['edit_link_template'];
			$component = new CreatePersonShortcode($wpdb, $group_id, $edit_link_template);
			return $component->render();
		}

		public function edit_person_shortcode($atts) {
			global $wp_query, $wpdb;
			$person_key = $wp_query->query_vars['group_id'];
			$component = new EditPersonShortcode($wpdb, $person_key);
			return $component->render();
		}

		public function signup_opens_countdown_shortcode($atts) {
			return CountdownShortcode::signup_opens($atts);
		}

		public function signup_closes_countdown_shortcode($atts) {
			return CountdownShortcode::signup_closes($atts);
		}

		public function form_opens_countdown_shortcode($atts) {
			return CountdownShortcode::submit_form_response_opens($atts);
		}

		public function form_closes_countdown_shortcode($atts) {
			return CountdownShortcode::submit_form_response_closes($atts);
		}

		public function query_vars($vars) {
			$vars[] = 'group_id';
			return $vars;
		}


		public function rewrite_rules($rules) {
			$rules = array('([^/]+)/([' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '})$' => 'single.php?pagename=$matches[1]&group_id=$matches[2]') + $rules;
			return $rules;
		}
	}
	
	new Frontend();