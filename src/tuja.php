<?php
/*
    Plugin Name: Tuja
    Description: Made for Tunnelbanejakten.se
    Version: 1.0.0
    Author: Mikael Svensson & Mattias Forsman
    Author URI: https://tunnelbanejakten.se
*/

namespace tuja;

abstract class Plugin {
	const VERSION = '1.0.0';
	const SLUG = 'tuja';
	const TABLE_PREFIX = 'tuja_';
	const FILE = __FILE__;
	const PATH = __DIR__;
	const EMAIL_ADDRESS = '';

	static public function get_url() {
		return plugin_dir_url(self::FILE);
	}

	public function __construct() {
		// Create/update database tables on activation
		register_activation_hook(self::FILE, array($this, 'install'));

		// Autoload all classes
		spl_autoload_register(array($this, 'autoloader'));
		
		$this->init();
	}

	public function init() {
		// Overridden by child classes
	}

	public function install() {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$tables = array();
		$charset = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_swedish_ci';
		
		$tables[] = '
			CREATE TABLE IF NOT EXISTS ' . DB::get_table('competition') . ' (
				id                   INTEGER AUTO_INCREMENT PRIMARY KEY,
				random_id            VARCHAR(20) NOT NULL UNIQUE,
				name                 VARCHAR(50) NOT NULL,
				payment_instructions VARCHAR(10000),
				create_group_start   INTEGER,
				create_group_end     INTEGER,
				edit_group_start     INTEGER,
				edit_group_end       INTEGER
			) ' . $charset;

		$tables[] = '
			CREATE TABLE IF NOT EXISTS ' . DB::get_table('team') . ' (
				id             INTEGER AUTO_INCREMENT PRIMARY KEY,
				random_id      VARCHAR(20)  NOT NULL UNIQUE,
				competition_id INTEGER      NOT NULL,
				name           VARCHAR(100) NOT NULL,
				type           VARCHAR(20)  NOT NULL,
				category_id    INTEGER,
				CONSTRAINT UNIQUE idx_team_token (random_id),
				CONSTRAINT UNIQUE idx_team_name (competition_id, name),
				CONSTRAINT fk_team_competition FOREIGN KEY (competition_id) REFERENCES ' . DB::get_table('competition') . ' (id)
					ON DELETE CASCADE,
				CONSTRAINT fk_team_category FOREIGN KEY (category_id) REFERENCES ' . DB::get_table('team_category') . ' (id)
					ON DELETE RESTRICT
			) ' . $charset;

		$tables[] = '
			CREATE TABLE IF NOT EXISTS ' . DB::get_table('person') . ' (
				id             INTEGER AUTO_INCREMENT PRIMARY KEY,
				random_id      VARCHAR(20)  NOT NULL,
				name           VARCHAR(100) NOT NULL,
				team_id        INTEGER      NOT NULL,
				phone          VARCHAR(50),
				phone_verified BOOLEAN      NOT NULL DEFAULT FALSE,
				email          VARCHAR(50),
				email_verified BOOLEAN      NOT NULL DEFAULT FALSE,
				CONSTRAINT UNIQUE idx_person_token (random_id),
				CONSTRAINT fk_person_team FOREIGN KEY (team_id) REFERENCES ' . DB::get_table('team') . ' (id)
					ON DELETE CASCADE
			) ' . $charset;

		$tables[] = '
			CREATE TABLE IF NOT EXISTS ' . DB::get_table('form') . ' (
				id                                INTEGER AUTO_INCREMENT PRIMARY KEY,
				competition_id                    INTEGER      NOT NULL,
				name                              VARCHAR(100) NOT NULL,
				allow_multiple_responses_per_team BOOLEAN      NOT NULL,
				submit_response_start             INTEGER,
				submit_response_end               INTEGER,
				CONSTRAINT fk_form_competition FOREIGN KEY (competition_id) REFERENCES ' . DB::get_table('competition') . ' (id)
					ON DELETE CASCADE
			) ' . $charset;

		$tables[] = "
			CREATE TABLE IF NOT EXISTS " . DB::get_table('form_question') . " (
				id         INTEGER AUTO_INCREMENT PRIMARY KEY,
				form_id    INTEGER      NOT NULL,
				type       VARCHAR(10)  NOT NULL CHECK (type IN ('text', 'number', 'header', 'pick_one', 'pick_multiple')),
				answer     VARCHAR(500),
				text       VARCHAR(500) NOT NULL,
				sort_order SMALLINT,
				text_hint  VARCHAR(500),
				CONSTRAINT fk_question_form FOREIGN KEY (form_id) REFERENCES " . DB::get_table('form') . " (id)
					ON DELETE CASCADE
			) " . $charset;

		$tables[] = '
			CREATE TABLE IF NOT EXISTS ' . DB::get_table('form_question_response') . ' (
				id               INTEGER AUTO_INCREMENT PRIMARY KEY,
				form_question_id INTEGER      NOT NULL,
				team_id          INTEGER      NOT NULL,
				answer           VARCHAR(500) NOT NULL,
				is_reviewed      BOOLEAN NOT  NULL DEFAULT FALSE,
				created_at       INTEGER,
				CONSTRAINT fk_form_question_response_question FOREIGN KEY (form_question_id) REFERENCES ' . DB::get_table('form_question') . ' (id)
					ON DELETE RESTRICT,
				CONSTRAINT fk_form_question_response_team FOREIGN KEY (team_id) REFERENCES ' . DB::get_table('team') . ' (id)
					ON DELETE CASCADE
			) ' . $charset;

		$tables[] = '
			CREATE TABLE IF NOT EXISTS ' . DB::get_table('form_question_points') . ' (
				form_question_id INTEGER NOT NULL,
				team_id          INTEGER NOT NULL,
				points           INTEGER,
				created_at       INTEGER,
				CONSTRAINT pk_form_question_points PRIMARY KEY (form_question_id, team_id),
				CONSTRAINT fk_form_question_points_question FOREIGN KEY (form_question_id) REFERENCES ' . DB::get_table('form_question') . ' (id)
					ON DELETE RESTRICT,
				CONSTRAINT fk_form_question_points_team FOREIGN KEY (team_id) REFERENCES ' . DB::get_table('team') . ' (id)
					ON DELETE CASCADE
		) ' . $charset;

		$tables[] = '
			CREATE TABLE IF NOT EXISTS ' . DB::get_table('message') . ' (
				id                INTEGER AUTO_INCREMENT PRIMARY KEY,
				form_question_id  INTEGER,
				team_id           INTEGER,
				text              VARCHAR(1000),
				image             VARCHAR(1000),
				source            VARCHAR(10),
				source_message_id VARCHAR(100),
				date_received     DATETIME,
				date_imported     DATETIME DEFAULT CURRENT_TIMESTAMP,
				CONSTRAINT fk_form_question_response_question FOREIGN KEY (form_question_id) REFERENCES ' . DB::get_table('form_question') . ' (id)
					ON DELETE RESTRICT,
				CONSTRAINT fk_form_question_response_team FOREIGN KEY (team_id) REFERENCES ' . DB::get_table('team') . ' (id)
					ON DELETE CASCADE
			) ' . $charset;

		$tables[] = '
			CREATE TABLE IF NOT EXISTS ' . DB::get_table('team_category') . ' (
				id             INTEGER          AUTO_INCREMENT PRIMARY KEY,
				competition_id INTEGER NOT NULL,
				is_crew        BOOLEAN NOT NULL DEFAULT FALSE,
				name           VARCHAR(20),
				CONSTRAINT fk_teamcategory_competition FOREIGN KEY (competition_id) REFERENCES competition (id)
					ON DELETE CASCADE
		) ' . $charset;

		foreach($tables as $table) {
			dbDelta($table);
		}
	}

	public function autoloader($name) {
		if(strncmp($name, __NAMESPACE__, strlen(__NAMESPACE__)) !== 0) return;
			
		$classname = explode('\\', $name);
		$classname = array_pop($classname);
		
		$paths = array(
			self::PATH . '/data/store/' . $classname . '.php',
			self::PATH . '/data/model/' . $classname . '.php',
			self::PATH . '/util/messaging/' . $classname . '.php',
			self::PATH . '/util/score/' . $classname . '.php',
			self::PATH . '/util/' . $classname . '.php',
			self::PATH . '/view/' . $classname . '.php',
			self::PATH . '/admin/' . $classname . '.php',
		);

		foreach($paths as $path) {
			if(!file_exists($path)) continue;
			
			include($path);
		}
	}
}

if(is_admin()) {
	require_once(Plugin::PATH . '/inc/admin.php');
}
else {
	require_once(Plugin::PATH . '/inc/frontend.php');
}
