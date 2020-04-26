<?php

namespace tuja\frontend;

use DateTime;
use tuja\data\model\Competition;
use tuja\data\model\GroupCategory;
use tuja\data\store\GroupCategoryDao;
use tuja\Frontend;
use tuja\util\Recaptcha;
use tuja\view\Field;
use WP_Post;

/**
 * Credits: https://gist.github.com/gmazzap/1efe17a8cb573e19c086
 */
abstract class FrontendView {

	const ACTION_BUTTON_NAME = 'tuja-action';
	const ACTION_NAME_SAVE = 'save';

	const FIELD_PREFIX_GROUP = 'tuja-group__';
	const FIELD_GROUP_NAME = self::FIELD_PREFIX_GROUP . 'name';
	const FIELD_GROUP_AGE = self::FIELD_PREFIX_GROUP . 'age';
	const FIELD_GROUP_NOTE = self::FIELD_PREFIX_GROUP . 'note';
	const FIELD_GROUP_EXTRA_CONTACT = self::FIELD_PREFIX_GROUP . 'extracontact';

	const FIELD_PREFIX_PERSON = 'tuja-person__';
	const FIELD_PERSON_NAME = self::FIELD_PREFIX_PERSON . 'name';
	const FIELD_PERSON_EMAIL = self::FIELD_PREFIX_PERSON . 'email';
	const FIELD_PERSON_PHONE = self::FIELD_PREFIX_PERSON . 'phone';
	const FIELD_PERSON_PNO = self::FIELD_PREFIX_PERSON . 'pno';
	const FIELD_PERSON_FOOD = self::FIELD_PREFIX_PERSON . 'food';
	const FIELD_PERSON_ROLE = self::FIELD_PREFIX_PERSON . 'role';
	const FIELD_PERSON_NOTE = self::FIELD_PREFIX_PERSON . 'note';

	private $wp_post = null;

	private $url;
	private $wp_template;
	private $category_dao;


	function __construct(
		$url,
		$wp_template = 'page.php'
	) {
		$this->url          = filter_var( $url, FILTER_SANITIZE_URL );
		$this->wp_template  = $wp_template;
		$this->category_dao = new GroupCategoryDao();
	}

	abstract function output();

	abstract function get_title();

	function get_content() {
		ob_start();

		Frontend::use_stylesheet( 'tuja-wp.css' );

		$this->output();

		return ob_get_clean();
	}

	function as_wp_post() {
		if ( is_null( $this->wp_post ) ) {
			$post          = array(
				'ID'             => 0,
				'post_title'     => $this->get_title(),
				'post_name'      => sanitize_title( $this->get_title() ),
				'post_content'   => $this->get_content() ?: '',
				'post_excerpt'   => '',
				'post_parent'    => 0,
				'menu_order'     => 0,
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'comment_count'  => 0,
				'post_password'  => '',
				'to_ping'        => '',
				'pinged'         => '',
				'guid'           => home_url( $this->url ),
				'post_date'      => current_time( 'mysql' ),
				'post_date_gmt'  => current_time( 'mysql', 1 ),
				'post_author'    => 0, // is_user_logged_in() ? get_current_user_id() : 0,
				'is_virtual'     => true,
				'filter'         => 'raw'
			);
			$this->wp_post = new WP_Post( (object) $post );
		}

		return $this->wp_post;
	}

	public function get_wp_template(): string {
		return $this->wp_template;
	}

	protected function render_field( Field $field, $field_name, $error_message, $answer_object = null ): string {
		// TODO: This is a bit of a hack...
		if ( is_scalar( $answer_object ) ) {
			$answer_object = [ $answer_object ];
		}
		$html = $field->render( $field_name, $answer_object, null, $error_message );

		return sprintf( '<div class="tuja-question %s">%s</div>',
			! empty( $error_message ) ? 'tuja-field-error' : '',
			$html );
	}

	protected function get_posted_category( $competition_id ) {
		$selected_category = $_POST[ self::FIELD_GROUP_AGE ];
		$categories        = $this->get_categories( $competition_id );
		$found_category    = array_filter( $categories, function ( GroupCategory $category ) use ( $selected_category ) {
			return $category->name == $selected_category;
		} );
		if ( count( $found_category ) == 1 ) {
			return reset( $found_category );
		}

		return null;
	}

	protected function get_categories( $competition_id ): array {
		$categories = array_filter( $this->category_dao->get_all_in_competition( $competition_id ), function ( $category ) {
			return ! $category->get_rules()->is_crew();
		} );

		return $categories;
	}

	protected function is_create_allowed( Competition $competition, GroupCategory $category ): bool {
		$now = new DateTime();
		if ( $competition->create_group_start != null && $competition->create_group_start > $now ) {
			return false;
		}
		if ( $competition->create_group_end != null && $competition->create_group_end < $now ) {
			return false;
		}

		return ! isset( $category ) || $category->get_rule_set()->is_create_registration_allowed( $competition );
	}

	private function get_recaptcha_site_key(): string {
		return get_option( 'tuja_recaptcha_sitekey' );
	}

	protected function get_recaptcha_html(): bool {
		$recaptcha_sitekey = $this->get_recaptcha_site_key();
		if ( ! empty( $recaptcha_sitekey ) ) {
			Frontend::use_script( 'https://www.google.com/recaptcha/api.js' );

			return sprintf( '<div class="tuja-robot-check"><div class="g-recaptcha" data-sitekey="%s"></div></div>', $recaptcha_sitekey );
		} else {
			return '';
		}
	}

	protected function validate_recaptcha_html() {
		$recaptcha_secret = get_option( 'tuja_recaptcha_sitesecret' );
		if ( ! empty( $recaptcha_secret ) ) {
			$recaptcha = new Recaptcha( $recaptcha_secret );
			$recaptcha->verify( $_POST['g-recaptcha-response'] );
		}
	}

}