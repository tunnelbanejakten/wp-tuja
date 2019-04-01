<?php
 /*
    Plugin Name: Tuja
    Description: Made for Tunnelbanejakten.se
    Version: 1.0.0
    Author: Mikael Svensson & Mattias Forsman
    Author URI: https://tunnelbanejakten.se
*/

namespace tuja;

use tuja\util\Database;
use tuja\util\Id;
use tuja\data\store\GroupDao;
use tuja\data\model\Response;
use tuja\data\store\ResponseDao;

abstract class Plugin
{
	const VERSION = '1.0.0';
	const SLUG = 'tuja';
	const TABLE_PREFIX = 'tuja_';
	const FILE = __FILE__;
	const PATH = __DIR__;
	const EMAIL_ADDRESS = '';

	static public function get_url()
	{
		return plugin_dir_url(self::FILE);
	}

	public function __construct()
	{
		// Create/update database tables on activation
		register_activation_hook(self::FILE, array($this, 'install'));

		// Autoload all classes
		spl_autoload_register(array($this, 'autoloader'));

		add_filter('query_vars', array($this, 'query_vars'));
		add_filter('rewrite_rules_array', array($this, 'rewrite_rules'));

		add_action('wp_ajax_tuja_upload_images', array($this, 'handle_image_upload'));
		add_action('wp_ajax_nopriv_tuja_upload_images', array($this, 'handle_image_upload'));

		$this->init();
	}



	/**
	 * Handles image uploads from FromShortcode via AJAX.
	 */
	public function handle_image_upload() {
		if(!empty($_FILES['file']) && !empty($_POST['group']) && !empty($_POST['question'])) {
			$group_dao = new GroupDao();
			$group = $group_dao->get_by_key( sanitize_text_field( $_POST['group'] ) );
			$question = (int)$_POST['question'];

			$response_dao = new ResponseDao();
			$response = $response_dao->get($group->id, $question, true);

			// Check lock
			$lock_res = $response_dao->get($group->id, 0, true);
			$lock_res = $lock_res->created->getTimestamp();
			$lock = (int)$_POST['lock'];
			if($lock === 0 || $lock_res !== $lock) {
				wp_send_json_error(array(
					'error' => 'Din bild kunde inte laddas upp eftersom någon annan i ditt lag har uppdaterat era svar. Ladda om sidan och prova igen.'
				), 401);
				exit;
			}

			$upload_dir = '/tuja/group-' . $group->random_id;
			add_filter('upload_dir', function ($dirs) use ($upload_dir) {
				$dirs['subdir'] = $upload_dir;
				$dirs['path'] = $dirs['basedir'] . $upload_dir;
				$dirs['url'] = $dirs['baseurl'] . $upload_dir;

				if(!file_exists($dirs['basedir'] . $upload_dir)) {
					mkdir($dirs['basedir'] . $upload_dir, 0777, true);
				}
			
				return $dirs;
			});

			$upload_overrides = array(
				'test_form' => false,
				'unique_filename_callback' => function($dir, $name, $ext) use($question) {
					$name = md5($name . $question);
					return $name.$ext;
				}
			);

			$file = $_FILES['file'];
			if(isset($file['error'][0])) {
				$file = array_map(function($e) { return $e[0]; }, $file);
			}

			$movefile = wp_handle_upload($file, $upload_overrides);

			if ( $movefile && ! isset( $movefile['error'] ) ) {
				$filename = explode('/', $movefile['file']);
				$filename = array_pop($filename);

				// Create new question response
				$response->answers[0] = json_decode($response->answers[0], true);

				if($response->answers[0]['images'][0] === '') {
					$response->answers[0]['images'][0] = $filename;
				} else {
					$response->answers[0]['images'][] = $filename;
				}

				$response->answers[0]['images'] = array_unique($response->answers[0]['images']);
				$response->answers[0] = json_encode($response->answers[0]);
				$response = $response_dao->create($response);

				wp_send_json(array(
					'error' => false,
					'image' => $filename,
					'lock' => $response->created_at
				));
				exit;
			}

			wp_send_json(array(
				'error' => 'Din bild kunde inte sparas. Ladda om sidan och prova igen eller kontakta kundtjänst.'
			), 500);
			exit;
		}

		wp_send_json(array(
			'error' => 'Datan som skickades är ogiltig.'
		), 400);
		exit;
	}

	public function init()
	{
		// Overridden by child classes
	}

	public function query_vars($vars)
	{
		$vars[] = 'group_id';

		return $vars;
	}

	public function rewrite_rules($rules)
	{
		$rules = array('([^/]+)/([' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '})/?$' => 'single.php?pagename=$matches[1]&group_id=$matches[2]') + $rules;

		return $rules;
	}

	public function install()
	{
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$tables = array();
		$charset = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_swedish_ci';

		$tables[] = '
			CREATE TABLE ' . Database::get_table('competition') . ' (
				id                   INTEGER AUTO_INCREMENT PRIMARY KEY,
				random_id            VARCHAR(20)    NOT NULL UNIQUE,
				name                 VARCHAR(100)   NOT NULL,
				payment_instructions TEXT,
				create_group_start   INTEGER,
				create_group_end     INTEGER,
				edit_group_start     INTEGER,
				edit_group_end       INTEGER,
				message_template_new_team_admin INTEGER,
				message_template_new_team_reporter INTEGER,
				message_template_new_crew_member INTEGER,
				message_template_new_noncrew_member INTEGER
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table('team') . ' (
				id             INTEGER AUTO_INCREMENT PRIMARY KEY,
				random_id      VARCHAR(20)  NOT NULL UNIQUE,
				competition_id INTEGER      NOT NULL,
				name           VARCHAR(100) NOT NULL,
				type           VARCHAR(20)  NOT NULL,
				category_id    INTEGER,
				UNIQUE KEY idx_team_token (random_id),
				UNIQUE KEY idx_team_name (competition_id, name)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table('person') . ' (
				id               INTEGER AUTO_INCREMENT PRIMARY KEY,
				random_id        VARCHAR(20)  NOT NULL,
				name             VARCHAR(100) NOT NULL,
				team_id          INTEGER      NOT NULL,
				phone            VARCHAR(100),
				phone_verified   BOOLEAN      NOT NULL DEFAULT FALSE,
				email            VARCHAR(100),
				email_verified   BOOLEAN      NOT NULL DEFAULT FALSE,
				pno              VARCHAR(16),
				food             TEXT,
				is_competing     BOOLEAN      NOT NULL DEFAULT TRUE,
				is_team_contact  BOOLEAN      NOT NULL DEFAULT FALSE,
				UNIQUE KEY idx_person_token (random_id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table('form') . ' (
				id                                INTEGER AUTO_INCREMENT PRIMARY KEY,
				competition_id                    INTEGER      NOT NULL,
				name                              VARCHAR(100) NOT NULL,
				allow_multiple_responses_per_team BOOLEAN      NOT NULL,
				submit_response_start             INTEGER,
				submit_response_end               INTEGER
			) ' . $charset;

		$tables[] = "
			CREATE TABLE " . Database::get_table('form_question') . " (
				id         INTEGER AUTO_INCREMENT PRIMARY KEY,
				form_id    INTEGER      NOT NULL,
				type       VARCHAR(100) NOT NULL,
				answer     TEXT,
				text       TEXT         NOT NULL,
				sort_order SMALLINT,
				text_hint  TEXT
			) " . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table('form_question_response') . ' (
				id               INTEGER AUTO_INCREMENT PRIMARY KEY,
				form_question_id INTEGER NOT NULL,
				team_id          INTEGER NOT NULL,
				answer           TEXT    NOT NULL,
				is_reviewed      BOOLEAN NOT NULL DEFAULT FALSE,
				created_at       INTEGER
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table('form_question_points') . ' (
				form_question_id INTEGER NOT NULL,
				team_id          INTEGER NOT NULL,
				points           INTEGER,
				created_at       INTEGER,
				PRIMARY KEY (form_question_id, team_id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table('message') . ' (
				id                INTEGER AUTO_INCREMENT PRIMARY KEY,
				form_question_id  INTEGER,
				team_id           INTEGER,
				text              TEXT,
				image             TEXT,
				source            VARCHAR(10),
				source_message_id VARCHAR(100),
				date_received     DATETIME,
				date_imported     DATETIME DEFAULT CURRENT_TIMESTAMP
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table('team_category') . ' (
				id             INTEGER          AUTO_INCREMENT PRIMARY KEY,
				competition_id INTEGER NOT NULL,
				is_crew        BOOLEAN NOT NULL DEFAULT FALSE,
				name           VARCHAR(100)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table('message_template') . ' (
				id             INTEGER AUTO_INCREMENT PRIMARY KEY,
				competition_id INTEGER NOT NULL,
				name           VARCHAR(100),
				subject        TEXT,
				body           TEXT
			) ' . $charset;

		$keys = array(
			['competition', 'message_template_new_team_admin', 'message_template', 'RESTRICT'],
			['competition', 'message_template_new_team_reporter', 'message_template', 'RESTRICT'],
			['competition', 'message_template_new_crew_member', 'message_template', 'RESTRICT'],
			['competition', 'message_template_new_noncrew_member', 'message_template', 'RESTRICT'],

			['team', 'competition_id', 'competition', 'CASCADE'],
			['team', 'category_id', 'team_category', 'RESTRICT'],

			['person', 'team_id', 'team', 'CASCADE'],

			['form', 'competition_id', 'competition', 'CASCADE'],

			['form_question_response', 'form_question_id', 'form_question', 'RESTRICT'],
			['form_question_response', 'team_id', 'team', 'CASCADE'],

			['form_question_points', 'form_question_id', 'form_question', 'RESTRICT'],
			['form_question_points', 'team_id', 'team', 'CASCADE'],

			['message', 'form_question_id', 'form_question', 'RESTRICT'],
			['message', 'team_id', 'team', 'CASCADE'],

			['team_category', 'competition_id', 'competition', 'CASCADE'],

			['message_template', 'competition_id', 'competition', 'CASCADE']
		);

		foreach ($tables as $table) {
			dbDelta($table);
		}

		try {
			Database::start_transaction();
			foreach ($keys as $key) {
				Database::add_foreign_key($key[0], $key[1], $key[2], $key[3]);
			}
			Database::commit();
		} catch (\Exception $e) {
			Database::rollback();
			error_log($e->getMessage());
		}
	}

	public function autoloader($name)
	{
		// Does $name start with our namespace?
		if (strncmp($name, __NAMESPACE__, strlen(__NAMESPACE__)) !== 0) return;

		$classname = explode('\\', $name);
		$classname = array_pop($classname);

		$paths = array(
			self::PATH . '/data/store/' . $classname . '.php',
			self::PATH . '/data/model/' . $classname . '.php',
			self::PATH . '/util/markdown/' . $classname . '.php',
			self::PATH . '/util/messaging/' . $classname . '.php',
			self::PATH . '/util/score/' . $classname . '.php',
			self::PATH . '/util/' . $classname . '.php',
			self::PATH . '/view/' . $classname . '.php',
			self::PATH . '/admin/' . $classname . '.php',
		);

		foreach ($paths as $path) {
			if (!file_exists($path)) continue;

			include($path);
		}
	}
}

if (is_admin()) {
	require_once(Plugin::PATH . '/inc/admin.php');
} else {
	require_once(Plugin::PATH . '/inc/frontend.php');
}
