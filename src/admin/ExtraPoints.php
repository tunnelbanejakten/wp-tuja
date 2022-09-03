<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\data\model\Points;
use tuja\data\model\Station;
use tuja\data\model\StationWeight;
use tuja\data\model\TicketDesign;
use tuja\data\store\StationDao;
use tuja\data\store\TicketDao;
use tuja\util\score\ScoreCalculator;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\store\CompetitionDao;
use tuja\data\model\ValidationException;
use tuja\data\store\ExtraPointsDao;

class ExtraPoints extends Competition {

	const MAGIC_NUMBER_NAME_FIELD_ID = -1;

	const ACTION_SAVE = 'save';

	private $extra_points_dao;

	public function __construct() {
		parent::__construct();
		$this->extra_points_dao = new ExtraPointsDao();
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( self::ACTION_SAVE === $_POST['tuja_action'] ) {
			$this->handle_action_save();
			try {
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
			}
		}
	}

	private function handle_action_save() {
		$group_dao = new GroupDao();

		$all_names = $this->all_names();

		$groups = $group_dao->get_all_in_competition( $this->competition->id );

		array_walk(
			$all_names,
			function ( string $name ) use ( $groups ) {
				array_walk(
					$groups,
					function ( Group $group ) use ( $name ) {
						$name_field_key   = self::get_field_key( $name, self::MAGIC_NUMBER_NAME_FIELD_ID );
						$points_field_key = self::get_field_key( $name, $group->id );

						$updated_name = ! empty( $_POST[ $name_field_key ] ) ? $_POST[ $name_field_key ] : $name;
						if ( isset( $_POST[ $points_field_key ] ) && is_numeric( $_POST[ $points_field_key ] ) ) {
							$points = intval( $_POST[ $points_field_key ] );
							$this->extra_points_dao->set( $group->id, $updated_name, $points );
						} else {
							$this->extra_points_dao->set( $group->id, $updated_name, null );
						}
					}
				);
			}
		);
	}

	private static function get_field_key( $name, $group_id ) {
		return join( '__', array( 'tuja', 'extra-points', crc32( $name ), $group_id ) );
	}

	public function get_scripts(): array {
		return array();
	}

	private function all_names(): array {
		$existing_names = $this->extra_points_dao->all_names( $this->competition->id );
		return array_merge( $existing_names, array( '' ) );
	}

	public function output() {
		$this->handle_post();

		$group_dao        = new GroupDao();
		$extra_points_dao = new ExtraPointsDao();

		$competition = $this->competition;

		$all_names = $this->all_names();

		$groups = $group_dao->get_all_in_competition( $this->competition->id );

		$points_by_key = array();
		$points        = $extra_points_dao->get_by_competition( $this->competition->id );
		array_walk(
			$points,
			function ( Points $points ) use ( &$points_by_key ) {
				$key                   = self::get_field_key( $points->name, $points->group_id );
				$points_by_key[ $key ] = $points->points;
			}
		);

		$back_url = add_query_arg(
			array(
				'tuja_competition' => $competition->id,
				'tuja_view'        => 'Stations',
			)
		);

		$save_button = sprintf(
			'
			<div class="tuja-buttons">
        		<button type="submit" class="button" name="tuja_action" value="%s">Spara</button>
    		</div>',
			self::ACTION_SAVE
		);

		include 'views/extra-points.php';
	}
}
