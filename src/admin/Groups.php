<?php

namespace tuja\admin;

use DateTime;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\Map;
use tuja\data\model\ValidationException;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\MapDao;
use tuja\data\store\PersonDao;
use tuja\data\store\ResponseDao;
use tuja\util\rules\RuleResult;

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

		$person_dao = new PersonDao();
		$group_dao  = new GroupDao();

		switch ( $_POST['tuja_action'] ) {
			case 'tuja_group_batch__category':
			case 'tuja_group_batch__status':
			case 'tuja_group_batch__map':
			case 'tuja_group_batch__alwayseditable':
				$selected_group_ids = array_map( 'intval', $_POST['tuja_group__selection'] );
				foreach ( $selected_group_ids as $selected_group_id ) {
					$group = $group_dao->get( $selected_group_id );
					if ( $group != false ) {
						try {
							switch ( $_POST['tuja_action'] ) {
								case 'tuja_group_batch__category':
									$group->category_id = intval( $_POST['tuja_group_batch__category'] );
									break;
								case 'tuja_group_batch__status':
									$group->set_status( $_POST['tuja_group_batch__status'] );
									break;
								case 'tuja_group_batch__map':
									$group->map_id = intval( $_POST['tuja_group_batch__map'] );
									break;
								case 'tuja_group_batch__alwayseditable':
									$group->is_always_editable = $_POST['tuja_group_batch__alwayseditable'] == 'yes';
									break;
							}
							$success = $group_dao->update( $group );
							if ( $success ) {
								AdminUtils::printSuccess( $group->name . ': Uppdaterad.' );
							} else {
								AdminUtils::printError( $group->name . ': Kunde inte uppdatera.' );
							}
						} catch ( Exception $e ) {
							AdminUtils::printError( $group->name . ': ' . $e->getMessage() );
						}
					}
				}
				break;
			case 'group_create':
				$props                 = new Group();
				$props->name           = $_POST['tuja_new_group_name'];
				$props->category_id    = $_POST['tuja_new_group_type'];
				$props->competition_id = $this->competition->id;

				try {
					$db_groups = new GroupDao();
					$group_id = $db_groups->create( $props );

					AdminUtils::printSuccess(
						sprintf(
							'<span
								id="tuja_new_group_message"
								data-group-id="%s"
								>Gruppen har skapats</span>',
							$group_id
						)
					);
	
					} catch ( Exception $e ) {
					AdminUtils::printException( $e );
				}
				break;
		}
	}

	public function get_scripts(): array {
		return [
			'admin-groups.js'
		];
	}

	public function output() {
		$this->handle_post();

		$db_groups           = new GroupDao();
		$db_group_categories = new GroupCategoryDao();
		$db_map = new MapDao();

		$competition = $this->competition;

		$groups_per_category = [];
		$groups_competing    = 0;
		$people_competing    = 0;
		$people_following    = 0;

		$groups_data = [];
		$groups      = $db_groups->get_all_in_competition( $competition->id, true );

		foreach ( $groups as $group ) {
			$group_data          = [];
			$group_data['model'] = $group;
			$group_data['fee']   = $group->effective_fee_calculator->calculate_fee( $group, new DateTime() );

			$registration_evaluation = $group->evaluate_registration();

			$group_data['registration_warning_count'] = count( array_filter( $registration_evaluation, function ( RuleResult $res ) {
				return $res->status === RuleResult::WARNING;
			} ) );

			$group_data['registration_blocker_count'] = count( array_filter( $registration_evaluation, function ( RuleResult $res ) {
				return $res->status === RuleResult::BLOCKER;
			} ) );

			$group_data['details_link']     = add_query_arg( array(
				'tuja_group'                                 => $group->id,
				'tuja_view'                                  => 'Group',
				\tuja\admin\Group::QUESTION_FILTER_URL_PARAM => ResponseDao::QUESTION_FILTER_ALL
			) );
			$group_data['category'] = $group->get_category();

			if ( ! $group_data['category']->get_rules()->is_crew() && $group->get_status() !== Group::STATUS_DELETED ) {
				$groups_competing                                     += 1;
				$people_competing                                     += $group->count_competing;
				$people_following                                     += $group->count_follower;
				@$groups_per_category[ $group_data['category']->name ] += 1;
			}

			$groups_data[] = $group_data;
		}

		$filter_configs = [
			[
				'Alla lag',
				function ( $group_data ) {
					return true;
				}
			],
			[
				'Tävlande lag',
				function ( $group_data ) {
					return ! $group_data['category']->get_rules()->is_crew();
				}
			],
			[
				'Lag på väntelistan',
				function ( $group_data ) {
					return $group_data['model']->get_status() == Group::STATUS_AWAITING_APPROVAL;
				}
			],
			[
				'Accepterade lag',
				function ( $group_data ) {
					return $group_data['model']->get_status() == Group::STATUS_ACCEPTED;
				}
			],
			[
				'Problem med anmälning',
				function ( $group_data ) {
					return $group_data['registration_blocker_count'] > 0;
				}
			],
			[
				'Dölj avanmälda',
				function ( $group_data ) {
					return $group_data['model']->get_status() !== Group::STATUS_DELETED;
				}
			]
		];
		$filter_index   = intval( @$_GET['tuja_group_filter'] ) ?: 0;
		$groups_data    = array_filter( $groups_data, $filter_configs[ $filter_index ][1] );
		$filters        = join( ', ',
			array_map(
				function ( $index, $filter ) use ( $filter_index ) {
					list ( $label ) = $filter;

					if ( $index == $filter_index ) {
						return sprintf( '<strong>%s</strong>', $label );
					}

					return sprintf( '<a href="%s">%s</a>', add_query_arg( array(
						'tuja_group_filter' => $index
					) ), $label );
				},
				array_keys( $filter_configs ),
				array_values( $filter_configs ) ) );

		$group_categories   = $db_group_categories->get_all_in_competition( $competition->id );
		$group_category_map = $this->categories_by_id( $group_categories );
		$maps               = $db_map->get_all_in_competition( $competition->id );
		$map_map            = $this->maps_by_id( $maps );

		include( 'views/groups.php' );
	}

	private function categories_by_id( array $group_categories ) {
		return array_combine(
			array_map( function ( GroupCategory $category ) {
				return $category->id;
			}, $group_categories ),
			array_map( function ( GroupCategory $category ) {
				return $category->name . ( $category->get_rules()->is_crew() ? ' (Funktionär)' : '' );
			}, $group_categories )
		);
	}

	private function maps_by_id( array $maps ) {
		return array_combine(
			array_map( function ( Map $map ) {
				return $map->id;
			}, $maps ),
			array_map( function ( Map $map ) {
				return $map->name;
			}, $maps )
		);
	}

}