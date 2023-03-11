<?php
namespace tuja\controller;

use tuja\data\model\Competition;
use tuja\data\model\Group;
use tuja\data\model\Duel;
use tuja\data\model\DuelInvite;
use tuja\data\store\DuelDao;
use tuja\data\store\GroupDao;
use tuja\util\schedule\DuelGroupSchedule;

class DuelsController {
	private $competition = null;
	private $duel_dao;
	private $group_dao;

	function __construct( Competition $competition ) {
		$this->competition = $competition;
		$this->duel_dao    = new DuelDao();
		$this->group_dao   = new GroupDao();
	}

	/**
	 * Deletes any existing duels and then generates new ones.
	 *
	 * We do this per map (city) so that teams only get invited to duels in their own city :-)
	 */
	public function generate_invites( int $groups_per_duel ) {
		$duel_groups          = $this->duel_dao->get_duels_by_competition( $this->competition->id, true );
		$duel_group_schedules = $this->create_duel_group_schedules( $duel_groups );

		$group_ids_per_map = $this->get_competing_group_ids_per_map();
		foreach ( $group_ids_per_map as $group_ids ) {
			foreach ( $duel_groups as $duel_group ) {
				$this->generate_map_invites( $duel_group->id, $group_ids, $groups_per_duel, $duel_group_schedules[ $duel_group->id ] );
			}
		}

	}

	private function get_competing_group_ids_per_map(): array {
		$competing_groups = array_filter(
			$this->group_dao->get_all_in_competition( $this->competition->id ),
			function ( Group $group ) {
				return ! $group->is_crew;
			}
		);
		return array_values(
			array_reduce(
				$competing_groups,
				function ( array $carry, Group $group ) {
					$carry[ $group->map_id ?? 0 ][] = $group->id;
					return $carry;
				},
				array()
			)
		);
	}

	/**
	 * Calculates when duels can be scheduled so that teams don't have to be at two places at once.
	 * (Assuming each team only has one duel in each duel group.)
	 *
	 * We first decide when duels can happen, in general, using the DuelGroupSchedule class.
	 * We then distribute these "duel time slots" evenly between the duel groups.
	 *
	 * This way, each duel group gets it's own set of unique time slots when duels can be scheduled.
	 */
	private function create_duel_group_schedules( array $duel_groups ) : array {
		$schedule_generator  = new DuelGroupSchedule( 0, array( 11 ) );
		$events              = $schedule_generator->generate( $this->competition );
		$duel_group_schedule = array();
		$i                   = 0;
		while ( ! empty( $events ) ) {
			$event                 = array_pop( $events );
			$duel_group_list_index = ( $i++ ) % count( $duel_groups );
			$duel_group_id         = $duel_groups[ $duel_group_list_index ]->id;

			$duel_group_schedule[ $duel_group_id ][] = $event;
		}
		return $duel_group_schedule;
	}

	// Cancel any duel invites for specified duel group for specified groups.
	// Create new duels with min_duel_participant_count or min_duel_participant_count+1 groups.
	// Groups are selected randomly from list of group_ids.
	private function generate_map_invites( int $duel_group_id, array $group_ids, int $min_duel_participant_count, array $schedule ) {
		$this->duel_dao->bulk_cancel_invites( $duel_group_id, $group_ids );

		$this->bulk_create_invites( $duel_group_id, $group_ids, $min_duel_participant_count, $schedule );
	}

	private function bulk_create_invites( int $duel_group_id, array $group_ids, int $min_duel_participant_count, array $schedule ) {
		$duels_group_ids = array();

		//
		// Step 1: Decide which groups will duel each other.
		//

		// First randomize the group list (since we'll process it start to end).
		shuffle( $group_ids );

		// Pick min_duel_participant_count number of teams at a time and group them together.
		while ( count( $group_ids ) >= $min_duel_participant_count ) {
			$duels_group_ids[] = array_splice( $group_ids, 0, $min_duel_participant_count, array() );
		}

		// Add remaining teams, in case the number of teams isn't a multiple of min_duel_participant_count,
		// to the other groups. We do this "round-robin style" so that there are a most min_duel_participant_count+1 teams per duel.
		$i = 0;
		while ( count( $group_ids ) > 0 ) {
			$duels_group_ids[ $i++ ][] = array_pop( $group_ids );
		}

		// Create the duels and invite the teams!
		foreach ( $duels_group_ids as $group_ids ) {
			// Create duel.
			$duel                = new Duel();
			$duel->duel_group_id = $duel_group_id;

			$duel->duel_at    = $schedule[ rand( 0, count( $schedule ) - 1 ) ];
			$duel->display_at = $this->competition->event_start;
			$duel_id          = $this->duel_dao->create_duel( $duel );
			// Invite groups in $group_ids.
			foreach ( $group_ids as $group_id ) {
				$invite          = new DuelInvite();
				$invite->duel_id = $duel_id;
				$invite->team_id = $group_id;
				$invite_id       = $this->duel_dao->create_invite( $invite );
			}
		}
	}
}
