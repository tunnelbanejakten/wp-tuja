<?php
namespace tuja\controller;

use tuja\data\model\Group;
use tuja\data\model\Points;
use tuja\data\model\Station;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\PersonDao;
use tuja\util\concurrency\LockValuesList;
use tuja\data\store\StationDao;
use tuja\data\store\StationPointsDao;
use tuja\util\Strings;

class ReportPointsController {
	private $participant_groups;

	private $points_dao;
	private $station_dao;
	private $category_dao;
	private $group_dao;
	private $person_dao;

	public function __construct() {
		$this->points_dao   = new StationPointsDao();
		$this->station_dao  = new StationDao();
		$this->category_dao = new GroupCategoryDao();
		$this->group_dao    = new GroupDao();
		$this->person_dao   = new PersonDao();
	}

	public function is_authorized( string $user_key ) {
		$person = $this->person_dao->get_by_key( $user_key );
		$group  = $this->group_dao->get( $person->group_id );

		$group_category = $group->get_category();

		$is_crew_group = isset( $group_category ) && $group_category->get_rules()->is_crew();
		return true === $is_crew_group;
	}

	public function get_all_points( Station $station ) {
		// Return points and lock value for each team for one station.

		$groups = $this->get_participant_groups( $station->competition_id );

		$current_points = array_filter(
			$this->points_dao->get_by_competition( $station->competition_id ),
			function ( Points $points ) use ( $station ) {
				return $points->station_id === $station->id;
			}
		);
		$points_by_key  = array_combine(
			array_map(
				function ( $points ) {
					return $points->group_id;
				},
				$current_points
			),
			array_values( $current_points )
		);

		return array_map(
			function ( Group $group ) use ( $points_by_key ) {
				$points = $points_by_key[ $group->id ] ?? null;
				$lock   = self::create_lock( $group, $points );

				return array(
					'group_id'   => $group->id,
					'group_name' => $group->name,
					'points'     => isset( $points ) ? $points->points : 0,
					'lock'       => $lock->to_string(),
				);
			},
			$groups
		);
	}

	private static function create_lock( Group $group, $points_object ): LockValuesList {
		$current_lock = new LockValuesList();
		if ( ! empty( $points_object ) ) {
			$current_lock->add_value( $group->id, $points_object->created->getTimestamp() );
		} else {
			$current_lock->add_value( $group->id, 0 );
		}
		return $current_lock;
	}

	private function get_current_points_object( Station $station, Group $group ) {
		$group_points   = $this->points_dao->get_by_group( $group->id );
		$current_points = current(
			array_filter(
				$group_points,
				function ( Points $points ) use ( $station ) {
					return $points->station_id === $station->id;
				}
			)
		);
		return $current_points;
	}

	public function set_points( Station $station, Group $group, $points, LockValuesList $user_lock ) {
		// Is lock valid?

		$current_points_object = $this->get_current_points_object( $station, $group );
		$current_lock          = self::create_lock( $group, $current_points_object );
		$invalid_ids           = $current_lock->get_invalid_ids( $user_lock );
		if ( count( $invalid_ids ) > 0 ) {
			return array(
				'error'       => Strings::get( 'crew_view.concurrent_update_error' ),
				'http_status' => 409,
			);
		}

		// Update points.
		$this->points_dao->set( $group->id, $station->id, is_numeric( $points ) ? intval( $points ) : null );

		$updated_points_object = $this->get_current_points_object( $station, $group );
		$updated_lock          = self::create_lock( $group, $updated_points_object );
		// Return new lock value.
		return array(
			'lock'        => $updated_lock->to_string(),
			'http_status' => 200,
		);
	}

	public function get_participant_groups( $competition_id ): array {
		if ( ! isset( $this->participant_groups ) ) {
			// TODO: DRY... Very similar code in Form.php
			$categories             = $this->category_dao->get_all_in_competition( $competition_id );
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

			$competition_groups       = $this->group_dao->get_all_in_competition( $competition_id );
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

}
