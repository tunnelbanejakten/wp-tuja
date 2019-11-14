<?php

namespace tuja\frontend;

use DateTime;
use tuja\data\model\Competition;
use tuja\data\model\GroupCategory;
use tuja\data\store\GroupCategoryDao;
use tuja\Frontend;
use tuja\view\Field;
use WP_Post;

/**
 * Credits: https://gist.github.com/gmazzap/1efe17a8cb573e19c086
 */
abstract class FrontendView {

	const ACTION_BUTTON_NAME = 'tuja-action';
	const ACTION_NAME_SAVE = 'save';

	const FIELD_PREFIX_PERSON = 'tuja-person__';
	const FIELD_PREFIX_GROUP = 'tuja-group__';
	const FIELD_GROUP_NAME = self::FIELD_PREFIX_GROUP . 'name';
	const FIELD_GROUP_AGE = self::FIELD_PREFIX_GROUP . 'age';

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

	abstract function render();

	abstract function get_title();

	function get_content() {
		ob_start();

		$this->render();

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
		$html = $field->render( $field_name, $answer_object );

		return sprintf( '<div class="tuja-question %s">%s%s</div>',
			! empty( $error_message ) ? 'tuja-field-error' : '',
			$html,
			! empty( $error_message ) ? sprintf( '<p class="tuja-message tuja-message-error">%s</p>', $error_message ) : '' );
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
			return ! $category->is_crew;
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

}