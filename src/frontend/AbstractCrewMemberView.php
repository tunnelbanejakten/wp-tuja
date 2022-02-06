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
			throw new Exception( 'Oj, inget lag eller användare angivet.' );
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
				throw new Exception( 'Bara funktionärer får använda detta formulär.' ); // TODO: Extract to strings.ini
			}

			return parent::get_content();
		} catch ( Exception $e ) {
			return $this->get_exception_message_html( $e );
		}
	}

	function get_title() {
		return $this->title;
	}

	abstract function get_optimistic_lock(): LockValuesList;

	protected function html_optimistic_lock(): string {
		$current_optimistic_lock = $this->get_optimistic_lock();
		$optimistic_lock_value   = $current_optimistic_lock->to_string();
		return  sprintf( '<input type="hidden" name="%s" value="%s">', self::OPTIMISTIC_LOCK_FIELD_NAME, htmlentities( $optimistic_lock_value ) );
	}

	protected function check_optimistic_lock() {
		$current_optimistic_lock   = $this->get_optimistic_lock();
		$submitted_optimistic_lock = LockValuesList::from_string( stripslashes( $_POST[ self::OPTIMISTIC_LOCK_FIELD_NAME ] ) );

		if ( count( $current_optimistic_lock->get_invalid_ids( $submitted_optimistic_lock ) ) > 0 ) {
			// TODO: Extract to strings.ini
			throw new Exception(
				'Någon annan har hunnit rapportera in andra poäng för dessa frågor/lag sedan du ' .
				'laddade den här sidan. För att undvika att du av misstag skriver över andra ' .
				'funktionärers poäng så sparades inte poängen du angav. De senast inrapporterade ' .
				'poängen visas istället för de du rapporterade in.'
			);
		}
	}

}
