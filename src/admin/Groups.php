<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\ValidationException;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PersonDao;
use tuja\data\store\ResponseDao;
use tuja\util\rules\PassthroughRuleSet;
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
			case 'anonymize':
				if ( $_POST['tuja_anonymizer_confirm'] !== 'true' ) {
					AdminUtils::printError( 'Du måste kryssa för att du verkligen vill anonymisera personuppgifterna först.' );

					return;
				}

				$exclude_contacts = false;

				$all_groups = $group_dao->get_all_in_competition( $this->competition->id, true );

				$competing_groups = array_filter( $all_groups, function ( Group $grp ) {
					return $grp->get_category()->get_rule_set()->is_crew();
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
				break;
			case 'group_create':
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
				break;
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
		$people_following    = 0;

		$category_unknown                      = new GroupCategory();
		$category_unknown->name                = 'okänd';
		$category_unknown->rule_set_class_name = PassthroughRuleSet::class;

		$groups_data = [];
		$groups      = $db_groups->get_all_in_competition( $competition->id, true );

		$unreviewed_answers = $this->get_unreviewed_answers_count();

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

			$group_data['details_link']     = add_query_arg( array(
				'tuja_group'                                 => $group->id,
				'tuja_view'                                  => 'Group',
				\tuja\admin\Group::QUESTION_FILTER_URL_PARAM => ResponseDao::QUESTION_FILTER_ALL
			) );
			$group_data['unreviewed_link']  = add_query_arg( array(
				'tuja_group'                                 => $group->id,
				'tuja_view'                                  => 'Group',
				\tuja\admin\Group::QUESTION_FILTER_URL_PARAM => ResponseDao::QUESTION_FILTER_UNREVIEWED_ALL
			) );
			$group_data['category']         = $group->get_category() ?: $category_unknown;
			$group_data['count_unreviewed'] = @$unreviewed_answers[ $group->id ] ?: 0;

			if ( ! $group_data['category']->get_rule_set()->is_crew() ) {
				$groups_competing                                     += 1;
				$people_competing                                     += $group->count_competing;
				$people_following                                     += $group->count_follower;
				$groups_per_category[ $group_data['category']->name ] += 1;
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
					return ! $group_data['category']->get_rule_set()->is_crew();
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
				'Orättade svar',
				function ( $group_data ) {
					return $group_data['count_unreviewed'] > 0;
				}
			],
			[
				'Problem med anmälning',
				function ( $group_data ) {
					return $group_data['registration_blocker_count'] > 0;
				}
			]
		];
		$filter_index   = intval( $_GET['tuja_group_filter'] ) ?: 0;
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

		include( 'views/groups.php' );
	}

	private function categories_by_id( array $group_categories ) {
		return array_combine(
			array_map( function ( GroupCategory $category ) {
				return $category->id;
			}, $group_categories ),
			array_map( function ( GroupCategory $category ) {
				return $category->name . ($category->get_rule_set()->is_crew() ? ' (Funktionär)' : '');
			}, $group_categories )
		);
	}

	private function get_unreviewed_answers_count(): array {
		$response_dao = new ResponseDao();
		$data         = $response_dao->get_by_questions(
			$this->competition->id,
			ResponseDao::QUESTION_FILTER_UNREVIEWED_ALL,
			[] );

		$unreviewed_answers = [];
		foreach ( $data as $form_id => $form_entry ) {
			foreach ( $form_entry['questions'] as $question_id => $question_entry ) {
				foreach ( $question_entry['responses'] as $group_id => $response_entry ) {
					$response = isset( $response_entry ) ? $response_entry['response'] : null;
					if ( isset( $response ) ) {
						$unreviewed_answers[ $response->group_id ] = ( @$unreviewed_answers[ $response->group_id ] ?: 0 ) + 1;
					}
				}
			}
		}

		return $unreviewed_answers;
	}
}