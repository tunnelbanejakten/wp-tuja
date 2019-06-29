<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\PersonDao;
use tuja\util\GroupCategoryCalculator;
use tuja\util\rules\RuleResult;
use tuja\data\store\GroupDao;
use tuja\data\store\CompetitionDao;
use tuja\data\model\ValidationException;

class Groups {

	private $competition;

	public function __construct() {
		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $_GET['tuja_competition'] );
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}
	}


	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] == 'group_update' ) {
			$form_values = array_filter( $_POST, function ( $key ) {
				return substr( $key, 0, strlen( 'tuja_group__' ) ) === 'tuja_group__';
			}, ARRAY_FILTER_USE_KEY );

			$db_groups = new GroupDao();
			$groups    = $db_groups->get_all_in_competition( $this->competition->id );

			$updated_groups = array_combine( array_map( function ( $g ) {
				return $g->id;
			}, $groups ), $groups );

			foreach ( $form_values as $field_name => $field_value ) {
				list( , $id, $attr ) = explode( '__', $field_name );
				switch ( $attr ) {
					case 'category':
						$updated_groups[ $id ]->category_id = intval( $field_value ) ?: null;
						break;
					case 'alwayseditable':
						$updated_groups[ $id ]->is_always_editable = $field_value == 'yes';
						break;
				}
			}

			foreach ( $updated_groups as $updated_group ) {
				try {
					$db_groups->update( $updated_group );
				} catch ( Exception $e ) {
					AdminUtils::printException( $e );
				}
			}
		} elseif ( $_POST['tuja_action'] == 'anonymize' ) {

			if ( $_POST['tuja_anonymizer_confirm'] !== 'true' ) {
				AdminUtils::printError( 'Du måste kryssa för att du verkligen vill anonymisera personuppgifterna först.' );

				return;
			}

			$person_dao       = new PersonDao();
			$group_dao        = new GroupDao();
			$exclude_contacts = false;

			$all_groups = $group_dao->get_all_in_competition( $this->competition->id );

			$category_calculator = new GroupCategoryCalculator( $this->competition->id );
			$competing_groups    = array_filter( $all_groups, function ( Group $grp ) use ( $category_calculator ) {
				$category = $grp->get_derived_group_category();

				return $category ? ! $category->is_crew : true;
			} );

			switch ( $_POST['tuja_anonymizer_filter'] ) {
				case 'participants':
					$groups           = $competing_groups;
					$exclude_contacts = false;
					break;
				case 'non_contacts':
					$groups           = $competing_groups;
					$exclude_contacts = true;
					break;
				default:
					$groups           = $all_groups;
					$exclude_contacts = false;
					break;
			}
			try {
				$group_ids = array_map( function ( Group $grp ) {
					return $grp->id;
				}, $groups );

				$person_dao->anonymize( $group_ids, $exclude_contacts );
				$group_dao->anonymize( $group_ids );

				AdminUtils::printSuccess( 'Klart. Personuppgifterna har anonymiserats.' );
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
			}
		} elseif ( $_POST['tuja_action'] == 'group_create' ) {
			$props                 = new Group();
			$props->name           = $_POST['tuja_new_group_name'];
			$props->category_id    = $_POST['tuja_new_group_type'];
			$props->competition_id = $this->competition->id;

			try {
				$db_groups = new GroupDao();
				$db_groups->create( $props );
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			}
		}
	}

	public function output() {
		$this->handle_post();

		$db_groups           = new GroupDao();
		$db_group_categories = new GroupCategoryDao();

		$competition = $this->competition;

		$groups_per_category = [];
		$groups_competing    = 0;
		$people_competing    = 0;

		$category_unknown          = new GroupCategory();
		$category_unknown->name    = 'okänd';
		$category_unknown->is_crew = false;

		$groups_data = [];
		$groups      = $db_groups->get_all_in_competition( $competition->id );

		foreach ( $groups as $group ) {
			$group_data          = [];
			$group_data['model'] = $group;

			$registration_evaluation = $group->evaluate_registration();

			$group_data['registration_warning_count'] = count( array_filter( $registration_evaluation, function ( RuleResult $res ) {
				return $res->status === RuleResult::WARNING;
			} ) );

			$group_data['registration_blocker_count'] = count( array_filter( $registration_evaluation, function ( RuleResult $res ) {
				return $res->status === RuleResult::BLOCKER;
			} ) );

			$group_data['details_link'] = add_query_arg( array(
				'tuja_group' => $group->id,
				'tuja_view'  => 'Group'
			) );
			$group_data['category']     = $group->get_derived_group_category() ?: $category_unknown;

			if ( ! $group_data['category']->is_crew ) {
				$groups_competing                                     += 1;
				$people_competing                                     += $group->count_competing;
				$groups_per_category[ $group_data['category']->name ] += 1;
			}

			$groups_data[] = $group_data;
		}

		$group_categories   = $db_group_categories->get_all_in_competition( $competition->id );
		$group_category_map = $this->categories_by_id( $group_categories );

		include( 'views/groups.php' );
	}

	private function categories_by_id( array $group_categories ) {
		return array_combine(
			array_map( function ( GroupCategory $category ) {
				return $category->id;
			}, $group_categories ),
			array_map( function ( GroupCategory $category ) {
				return sprintf( '%s (%s)',
					$category->name,
					$category->is_crew ? 'Funktionär' : 'Tävlande' );
			}, $group_categories )
		);
	}
}