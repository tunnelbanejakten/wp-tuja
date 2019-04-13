<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\data\store\GroupCategoryDao;
use tuja\util\GroupCategoryCalculator;
use tuja\util\rules\RegistrationEvaluator;
use tuja\util\score\ScoreCalculator;
use tuja\data\store\FormDao;
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
				}
			}

			foreach ( $updated_groups as $updated_group ) {
				try {
					$db_groups->update( $updated_group );
				} catch ( Exception $e ) {
					AdminUtils::printException( $e );
				}
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

		$groups           = $db_groups->get_all_in_competition( $competition->id );
		$group_categories = $db_group_categories->get_all_in_competition( $competition->id );

		$category_calculator = new GroupCategoryCalculator( $competition->id );

		$registration_evaluator  = new RegistrationEvaluator();

		include( 'views/groups.php' );
	}
}