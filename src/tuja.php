<?php
/*
   Plugin Name: Tuja
   Description: Made for Tunnelbanejakten.se
   Version: 1.0.0
   Author: Mikael Svensson & Mattias Forsman
   Author URI: https://tunnelbanejakten.se
*/

namespace tuja;

use Exception;
use tuja\util\Database;

abstract class Plugin {
	const VERSION = '1.0.0';
	const SLUG = 'tuja';
	const TABLE_PREFIX = 'tuja_';
	const FILE = __FILE__;
	const PATH = __DIR__;
	const EMAIL_ADDRESS = '';

	static public function get_url() {
		return plugin_dir_url( self::FILE );
	}

	public function __construct() {
		// Create/update database tables on activation
		register_activation_hook( self::FILE, array( $this, 'install' ) );

		// Autoload all classes
		spl_autoload_register( array( $this, 'autoloader' ) );

		// Composer
		if(!@include_once(self::PATH . '/vendor/autoload.php')) {
			die('Composer is not initialized.');
		}

		add_action( 'wp_ajax_tuja_upload_images', array( 'tuja\util\ImageManager', 'handle_image_upload' ) );
		add_action( 'wp_ajax_nopriv_tuja_upload_images', array( 'tuja\util\ImageManager', 'handle_image_upload' ) );

		add_filter( 'allowed_http_origins', array( $this, 'add_allowed_origins' ) );

		$this->init();
	}

	function add_allowed_origins( $origins ) {
		$origins[] = 'https://tunnelbanejakten.se';
		$origins[] = 'https://app.tunnelbanejakten.se';
		$origins[] = 'http://localhost:8081';
		return $origins;
	}

	public function init() {
		// Overridden by child classes
	}

	public function install() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$tables  = array();
		$charset = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_swedish_ci';

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'competition' ) . ' (
				id                                   INTEGER AUTO_INCREMENT NOT NULL,
				random_id                            VARCHAR(20)    NOT NULL,
				name                                 VARCHAR(100)   NOT NULL,
				payment_instructions                 TEXT,
				create_group_start                   INTEGER,
				create_group_end                     INTEGER,
				edit_group_start                     INTEGER,
				edit_group_end                       INTEGER,
				event_start                          INTEGER,
				event_end                            INTEGER,
				message_template_new_team_admin      INTEGER,
				message_template_new_team_reporter   INTEGER,
				message_template_new_crew_member     INTEGER,
				message_template_new_noncrew_member  INTEGER,
				initial_group_status                 VARCHAR(20),
				PRIMARY KEY (id),
				UNIQUE KEY idx_competition_token (random_id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'team' ) . ' (
				id                 INTEGER AUTO_INCREMENT NOT NULL,
				random_id          VARCHAR(20)  NOT NULL,
				competition_id     INTEGER      NOT NULL,
				map_id             INTEGER,
				is_always_editable BOOLEAN      NOT NULL DEFAULT FALSE,
				PRIMARY KEY (id),
				UNIQUE KEY idx_team_token (random_id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'team_properties' ) . ' (
				id             INTEGER AUTO_INCREMENT NOT NULL,
				team_id        INTEGER      NOT NULL,
				created_at     INTEGER      NOT NULL,
				status         VARCHAR(20)  NOT NULL,
				name           VARCHAR(100) NOT NULL,
				city           VARCHAR(30),
				category_id    INTEGER,
				note           TEXT,
				PRIMARY KEY (id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'person' ) . ' (
				id               INTEGER AUTO_INCREMENT NOT NULL,
				random_id        VARCHAR(20)  NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY idx_person_token (random_id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'person_properties' ) . ' (
				id                INTEGER AUTO_INCREMENT NOT NULL,
				person_id         INTEGER      NOT NULL,
				created_at        INTEGER      NOT NULL,
				status            VARCHAR(20)  NOT NULL,
				name              VARCHAR(100) NOT NULL,
				team_id           INTEGER      NOT NULL,
				phone             VARCHAR(100),
				phone_verified    BOOLEAN      NOT NULL DEFAULT FALSE,
				email             VARCHAR(100),
				email_verified    BOOLEAN      NOT NULL DEFAULT FALSE,
				pno               VARCHAR(16),
				food              TEXT,
				is_competing      BOOLEAN      NOT NULL DEFAULT TRUE,
				is_team_contact   BOOLEAN      NOT NULL DEFAULT FALSE,
				is_attending      BOOLEAN      NOT NULL DEFAULT TRUE,
				note              TEXT,
				PRIMARY KEY (id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'form' ) . ' (
				id                                INTEGER AUTO_INCREMENT NOT NULL,
				random_id                         VARCHAR(20),
				competition_id                    INTEGER      NOT NULL,
				name                              VARCHAR(100) NOT NULL,
				allow_multiple_responses_per_team BOOLEAN      NOT NULL,
				submit_response_start             INTEGER,
				submit_response_end               INTEGER,
				PRIMARY KEY (id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'form_question_group' ) . ' (
				id         INTEGER AUTO_INCREMENT NOT NULL,
				random_id  VARCHAR(20)  NOT NULL,
				form_id    INTEGER      NOT NULL,
				text       TEXT,
				sort_order SMALLINT,
				config     TEXT,
				PRIMARY KEY (id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'form_question' ) . ' (
				id                 INTEGER AUTO_INCREMENT NOT NULL,
				random_id          VARCHAR(20),
				form_id            INTEGER,
				question_group_id  INTEGER,
				type               VARCHAR(100) NOT NULL,
				answer             TEXT,
				text               TEXT         NOT NULL,
				sort_order         SMALLINT,
				limit_time         SMALLINT,
				text_hint          TEXT,
				PRIMARY KEY (id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'map' ) . ' (
				id                 INTEGER AUTO_INCREMENT NOT NULL,
				random_id          VARCHAR(20)  NOT NULL,
				competition_id     INTEGER      NOT NULL,
				name               VARCHAR(100),
				PRIMARY KEY (id),
				UNIQUE KEY idx_map_token (random_id)
				) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'marker' ) . ' (
				id                     INTEGER AUTO_INCREMENT NOT NULL,
				random_id              VARCHAR(20) NOT NULL,
				map_id                 INTEGER NOT NULL,
				gps_coord_lat          DOUBLE NOT NULL,
				gps_coord_long         DOUBLE NOT NULL,
				type                   VARCHAR(100) NOT NULL,
				name                   VARCHAR(100),
				description            TEXT,
				link_form_id           INTEGER,
				link_form_question_id  INTEGER,
				link_question_group_id INTEGER,
				link_station_id        INTEGER,
				PRIMARY KEY (id),
				UNIQUE KEY idx_marker_token (random_id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'form_question_response' ) . ' (
				id               INTEGER AUTO_INCREMENT NOT NULL,
				form_question_id INTEGER NOT NULL,
				team_id          INTEGER NOT NULL,
				answer           TEXT    NOT NULL,
				is_reviewed      BOOLEAN NOT NULL DEFAULT FALSE,
				created_at       INTEGER,
				PRIMARY KEY (id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'form_question_points' ) . ' (
				form_question_id INTEGER NOT NULL,
				team_id          INTEGER NOT NULL,
				points           INTEGER,
				created_at       INTEGER,
				PRIMARY KEY (form_question_id, team_id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'message' ) . ' (
				id                INTEGER AUTO_INCREMENT NOT NULL,
				form_question_id  INTEGER,
				team_id           INTEGER,
				text              TEXT,
				image             TEXT,
				source            VARCHAR(10),
				source_message_id VARCHAR(100),
				date_received     DATETIME,
				date_imported     DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'team_category' ) . ' (
				id                  INTEGER AUTO_INCREMENT NOT NULL,
				competition_id      INTEGER NOT NULL,
				is_crew             BOOLEAN NOT NULL DEFAULT FALSE,
				name                VARCHAR(100),
				rule_set            VARCHAR(100),
				rules_configuration TEXT,
				PRIMARY KEY (id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'message_template' ) . ' (
				id                      INTEGER AUTO_INCREMENT NOT NULL,
				competition_id          INTEGER NOT NULL,
				name                    VARCHAR(100),
				subject                 TEXT,
				body                    TEXT,
				auto_send_trigger       VARCHAR(100),
				auto_send_recipient     VARCHAR(100),
				delivery_method         VARCHAR(10),
				PRIMARY KEY (id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'string' ) . ' (
				competition_id  INTEGER NOT NULL,
				name            VARCHAR(100),
				value           TEXT,
				PRIMARY KEY (competition_id, name)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'station' ) . ' (
				id                      INTEGER AUTO_INCREMENT NOT NULL,
				random_id               VARCHAR(20),
				competition_id          INTEGER NOT NULL,
				name                    VARCHAR(100),
				location_gps_coord_lat  DOUBLE,
				location_gps_coord_long DOUBLE,
				location_description    TEXT,
				PRIMARY KEY (id),
				UNIQUE KEY idx_station_token (random_id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'ticket' ) . ' (
				team_id                   INTEGER NOT NULL,
				station_id                INTEGER NOT NULL,
				on_complete_password_used VARCHAR(100),
				PRIMARY KEY (team_id, station_id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'ticket_station_config' ) . ' (
				station_id           INTEGER NOT NULL,
				colour               VARCHAR(100),
				word                 VARCHAR(100),
				symbol               TEXT,
				on_complete_password VARCHAR(100),
				PRIMARY KEY (station_id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'ticket_coupon_weight' ) . ' (
				from_station_id   INTEGER NOT NULL,
				to_station_id     INTEGER NOT NULL,
				to_weight         DOUBLE NOT NULL,
				PRIMARY KEY (from_station_id, to_station_id)
			) ' . $charset;

		$tables[] = '
			CREATE TABLE ' . Database::get_table( 'event' ) . ' (
				id                    INTEGER AUTO_INCREMENT NOT NULL,
				competition_id        INTEGER NOT NULL,
				created_at            INTEGER NOT NULL,
				event_name            VARCHAR(50) NOT NULL,
				event_data            TEXT,
				team_id               INTEGER,
				person_id             INTEGER,
				object_type           VARCHAR(50),
				object_id             INTEGER,
				PRIMARY KEY (id),
				INDEX idx_event_event (event_name),
				INDEX idx_event_object (object_type, object_id)
			) ' . $charset;

		$keys = array(
			[ 'competition', 'message_template_new_team_admin', 'message_template', 'RESTRICT' ], // No longer used
			[ 'competition', 'message_template_new_team_reporter', 'message_template', 'RESTRICT' ], // No longer used
			[ 'competition', 'message_template_new_crew_member', 'message_template', 'RESTRICT' ], // No longer used
			[ 'competition', 'message_template_new_noncrew_member', 'message_template', 'RESTRICT' ], // No longer used

			[ 'team', 'map_id', 'map', 'RESTRICT' ],
			[ 'team', 'competition_id', 'competition', 'CASCADE' ],
			[ 'team_properties', 'team_id', 'team', 'CASCADE' ],
			[ 'team_properties', 'category_id', 'team_category', 'RESTRICT' ],

			[ 'person_properties', 'person_id', 'person', 'CASCADE' ],
			[ 'person_properties', 'team_id', 'team', 'CASCADE' ],

			[ 'form', 'competition_id', 'competition', 'CASCADE' ],

			[ 'form_question_group', 'form_id', 'form', 'CASCADE' ],

			[ 'form_question', 'question_group_id', 'form_question_group', 'CASCADE' ],

			[ 'form_question_response', 'form_question_id', 'form_question', 'RESTRICT' ],
			[ 'form_question_response', 'team_id', 'team', 'CASCADE' ],

			[ 'form_question_points', 'form_question_id', 'form_question', 'RESTRICT' ],
			[ 'form_question_points', 'team_id', 'team', 'CASCADE' ],

			[ 'message', 'form_question_id', 'form_question', 'RESTRICT' ],
			[ 'message', 'team_id', 'team', 'CASCADE' ],

			[ 'team_category', 'competition_id', 'competition', 'CASCADE' ],

			[ 'message_template', 'competition_id', 'competition', 'CASCADE' ],

			array( 'event', 'competition_id', 'competition', 'CASCADE' ),
			array( 'event', 'person_id', 'person', 'CASCADE' ),
			array( 'event', 'team_id', 'team', 'CASCADE' ),

			array( 'station', 'competition_id', 'competition', 'CASCADE' ),

			array( 'map', 'competition_id', 'competition', 'CASCADE' ),

			array( 'marker', 'map_id', 'map', 'CASCADE' ),
			array( 'marker', 'link_form_id', 'form', 'CASCADE' ),
			array( 'marker', 'link_form_question_id', 'form_question', 'CASCADE' ),
			array( 'marker', 'link_question_group_id', 'form_question_group', 'CASCADE' ),
			array( 'marker', 'link_station_id', 'station', 'CASCADE' ),

			[ 'ticket', 'team_id', 'team', 'CASCADE' ],
			[ 'ticket', 'station_id', 'station', 'CASCADE' ],
			[ 'ticket_station_config', 'station_id', 'station', 'CASCADE' ],
			[ 'ticket_coupon_weight', 'from_station_id', 'station', 'CASCADE' ],
			[ 'ticket_coupon_weight', 'to_station_id', 'station', 'CASCADE' ]
		);

		foreach ( $tables as $table ) {
			dbDelta( $table );
		}

		try {
			Database::start_transaction();
			foreach ( $keys as $key ) {
				Database::add_foreign_key( $key[0], $key[1], $key[2], $key[3] );
			}

			Database::set_missing_form_keys();

			Database::commit();
		} catch ( Exception $e ) {
			Database::rollback();
			error_log( $e->getMessage() );
		}
	}

	public function autoloader( $name ) {
		// Does $name start with our namespace?
		if ( strncmp( $name, __NAMESPACE__, strlen( __NAMESPACE__ ) ) !== 0 ) {
			return;
		}

		$classname = explode( '\\', $name );
		$classname = array_pop( $classname );

		$paths = array(
			self::PATH . '/data/store/' . $classname . '.php',
			self::PATH . '/data/model/' . $classname . '.php',
			self::PATH . '/util/anonymizer/' . $classname . '.php',
			self::PATH . '/data/model/question/' . $classname . '.php',
			self::PATH . '/util/router/' . $classname . '.php',
			self::PATH . '/util/markdown/' . $classname . '.php',
			self::PATH . '/util/formattedtext/' . $classname . '.php',
			self::PATH . '/util/messaging/' . $classname . '.php',
			self::PATH . '/util/concurrency/' . $classname . '.php',
			self::PATH . '/util/rules/' . $classname . '.php',
			self::PATH . '/util/score/' . $classname . '.php',
			self::PATH . '/util/ticket/' . $classname . '.php',
			self::PATH . '/util/fee/' . $classname . '.php',
			self::PATH . '/util/paymentoption/' . $classname . '.php',
			self::PATH . '/util/' . $classname . '.php',
			self::PATH . '/view/' . $classname . '.php',
			self::PATH . '/admin/' . $classname . '.php',
			self::PATH . '/frontend/' . $classname . '.php',
			self::PATH . '/frontend/router/' . $classname . '.php',
			self::PATH . '/api/' . $classname . '.php',
			self::PATH . '/inc/' . $classname . '.php',
		);

		foreach ( $paths as $path ) {
			if ( ! file_exists( $path ) ) {
				continue;
			}

			include_once( $path );
		}
	}
}

if ( is_admin() ) {
	require_once( Plugin::PATH . '/inc/admin.php' );
} else {
	require_once( Plugin::PATH . '/inc/frontend.php' );
}

require_once( Plugin::PATH . '/inc/api.php' );