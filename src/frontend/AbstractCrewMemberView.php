<?php

namespace tuja\frontend;

use tuja\frontend\FrontendView;
use DateTime;
use Exception;
use Throwable;
use tuja\data\model\Group;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PersonDao;
use tuja\util\concurrency\LockValuesList;
use tuja\util\Strings;

abstract class AbstractCrewMemberView extends FrontendView {

	const FIELD_NAME_PART_SEP        = '__';
	const FORM_PREFIX                = 'tuja_crewview';
	const ACTION_FIELD_NAME          = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'action';
	const OPTIMISTIC_LOCK_FIELD_NAME = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'optimistic_lock';

	protected $competition_id = null;

	protected function __construct( string $url, string $group_or_person_key, string $title ) {
		parent::__construct( $url );
		$this->group_dao           = new GroupDao();
		$this->person_dao          = new PersonDao();
		$this->competition_dao     = new CompetitionDao();
		$this->category_dao        = new GroupCategoryDao();
		$this->group_or_person_key = $group_or_person_key;
		$this->title               = $title;
	}

	private function init_user_or_group() {
		$group_or_person_key = $this->group_or_person_key;
		if ( isset( $group_or_person_key ) ) {

			$person = $this->person_dao->get_by_key( $group_or_person_key );
			if ( $person ) {
				$this->person = $person;
				$this->group  = $this->group_dao->get( $person->group_id );
			} else {
				$group = $this->group_dao->get_by_key( $group_or_person_key );
				if ( $group ) {
					$this->person = null;
					$this->group  = $group;
				} else {
					throw new Exception( 'Oj, vi hittade varken lag eller användare.' );
				}
			}

			$this->competition_id = $this->group->competition_id;
			Strings::init( $this->competition_id );

			if ( $this->group->get_status() == Group::STATUS_DELETED ) {
				throw new Exception( Strings::get( 'group.is_deleted' ) );
			}
		} else {
			throw new Exception( Strings::get( 'crew_view.no_group_or_person_specified' ) );
		}
	}

	protected function get_person() {
		return $this->person;
	}

	protected function get_group(): Group {
		return $this->group;
	}

	protected function get_participant_groups(): array {
		if ( ! isset( $this->participant_groups ) ) {
			// TODO: DRY... Very similar code in Form.php
			$categories             = $this->category_dao->get_all_in_competition( $this->competition_id );
			$participant_categories = array_filter(
				$categories,
				function ( $category ) {
					return ! $category->get_rules()->is_crew();
				}
			);
			$ids                    = array_map(
				function ( $category ) {
					return $category->id;
				},
				$participant_categories
			);

			$competition_groups       = $this->group_dao->get_all_in_competition( $this->competition_id );
			$this->participant_groups = array_filter(
				$competition_groups,
				function ( Group $group ) use ( $ids ) {
					$group_category = $group->get_category();

					return isset( $group_category ) && in_array( $group_category->id, $ids );
				}
			);
		}

		return $this->participant_groups;
	}

	function get_content() {
		try {
			$this->init_user_or_group();

			// Validate group category
			$group_category = $this->get_group()->get_category();
			if ( isset( $group_category ) && ! $group_category->get_rules()->is_crew() ) {
				throw new Exception( Strings::get( 'crew_view.crew_members_only' ) );
			}

			return parent::get_content();
		} catch ( Exception $e ) {
			return $this->get_exception_message_html( $e );
		}
	}

	function get_title() {
		return $this->title;
	}

	function handle_post():string {
		if ( $this->is_save_request() ) {
			$errors = $this->update_points();
			if ( empty( $errors ) ) {
				return sprintf( '<p class="tuja-message tuja-message-success">%s</p>', Strings::get( 'crew_view.points_have_been_updated' ) );
			} else {
				return sprintf( '<p class="tuja-message tuja-message-error">%s</p>', join( '. ', $errors ) );
			}
		}
		return '';
	}

	abstract function get_optimistic_lock(): LockValuesList;

	abstract function update_points(): array;

	protected function html_save_button(): string {
		return sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="update">%s</button></div>', self::ACTION_FIELD_NAME, Strings::get( 'crew_view.update_button' ) );
	}

	private function is_save_request(): bool {
		return isset( $_POST[ self::ACTION_FIELD_NAME ] ) && $_POST[ self::ACTION_FIELD_NAME ] == 'update';
	}

	protected function html_optimistic_lock(): string {
		$current_optimistic_lock = $this->get_optimistic_lock();
		$optimistic_lock_value   = $current_optimistic_lock->to_string();
		return  sprintf( '<input type="hidden" name="%s" value="%s">', self::OPTIMISTIC_LOCK_FIELD_NAME, htmlentities( $optimistic_lock_value ) );
	}

	protected function check_optimistic_lock() {
		$current_optimistic_lock   = $this->get_optimistic_lock();
		$submitted_optimistic_lock = LockValuesList::from_string( stripslashes( $_POST[ self::OPTIMISTIC_LOCK_FIELD_NAME ] ) );

		if ( count( $current_optimistic_lock->get_invalid_ids( $submitted_optimistic_lock ) ) > 0 ) {
			throw new Exception( Strings::get( 'crew_view.concurrent_update_error' ) );
		}
	}

}
