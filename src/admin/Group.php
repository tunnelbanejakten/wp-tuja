<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\QuestionGroup;
use tuja\data\store\CompetitionDao;
use tuja\data\store\GroupDao;
use tuja\data\store\EventDao;
use tuja\data\store\FormDao;
use tuja\data\store\MessageDao;
use tuja\data\store\QuestionPointsOverrideDao;
use tuja\data\store\StationPointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\ResponseDao;
use tuja\data\store\StationDao;
use tuja\util\JwtUtils;

class Group extends Groups {

	private $question_group_dao;
	// private $form_dao;

	public function __construct() {
		parent::__construct();
		$this->question_group_dao = new QuestionGroupDao();
		$this->group              = $this->group_dao->get( $_GET['tuja_group'] );
		$this->assert_set( 'Could not find group', $this->group );
		$this->assert_same( 'Group is from different competition', $this->competition->id, $this->group->competition_id );
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		$groups_current = null;
		$groups_links   = array();
		$dao            = new GroupDao();
		$groups         = $dao->get_all_in_competition( $this->competition->id );
		foreach ( $groups as $group ) {
			if ( $group->id === $this->group->id ) {
				$groups_current = $group->name;
			}
			$link           = add_query_arg(
				array(
					'tuja_competition' => $this->competition->id,
					'tuja_view'        => 'Group',
					'tuja_group'       => $group->id,
				)
			);
			$groups_links[] = BreadcrumbsMenu::item( $group->name, $link );
		}

		$menu->add(
			BreadcrumbsMenu::item( $groups_current ),
			...$groups_links,
		);

		return $this->add_static_menu(
			$menu,
			array(
				Group::class        => 'Allmänt',
				GroupMembers::class => 'Deltagare',
				GroupLinks::class   => 'Länkar',
				GroupScore::class   => 'Svar och poäng',
				GroupEvents::class  => 'Tidsbegränsade frågor som visats',
			)
		);
	}

	public function handle_post() {
		global $wpdb;

		if ( ! isset( $_POST['tuja_points_action'] ) ) {
			return;
		}

		@list( $action, $parameter ) = explode( '__', @$_POST['tuja_points_action'] );

		if ( $action === 'save_group' ) {
			// Fee calculator
			$this->group->fee_calculator = AdminUtils::get_fee_configuration_object( 'tuja_group_fee_calculator' );

			$success = $this->group_dao->update( $this->group );

			if ( $success ) {
				$this->group = $this->group_dao->get( $_GET['tuja_group'] );
				AdminUtils::printSuccess( 'Ändringar sparade.' );
			} else {
				AdminUtils::printError( 'Kunde inte spara.' );
			}
		} elseif ( $action === 'transition' ) {

			$this->group->set_status( $parameter );

			$success = $this->group_dao->update( $this->group );

			if ( $success ) {
				$this->group = $this->group_dao->get( $_GET['tuja_group'] );
				AdminUtils::printSuccess(
					sprintf(
						'Status har ändrats till %s.',
						$this->group->get_status()
					)
				);
			} else {
				AdminUtils::printError(
					sprintf(
						'Kunde inte ändra till %s.',
						$parameter
					)
				);
			}
		}
	}

	public function print_fee_configuration_form() {
		return AdminUtils::print_fee_configuration_form(
			$this->group->fee_calculator,
			'tuja_group_fee_calculator',
			true
		);
	}

	public function get_scripts(): array {
		return array(
			'admin-formgenerator.js',
			'jsoneditor.min.js',
			'admin-group.js',
		);
	}

	public function output() {
		$this->handle_post();

		$messages_manager = new MessagesManager( $this->competition );
		$messages_manager->handle_post();

		$competition = $this->competition;

		$db_form           = new FormDao();
		$forms             = $db_form->get_all_in_competition( $competition->id );
		$db_question       = new QuestionDao();
		$db_question_group = new QuestionGroupDao();
		$db_response       = new ResponseDao();
		$db_points         = new QuestionPointsOverrideDao();
		$db_station_points = new StationPointsDao();
		$db_stations       = new StationDao();
		$db_message        = new MessageDao();
		$db_event          = new EventDao();

		$question_groups = $this->question_group_dao->get_all_in_competition( $competition->id );
		$question_groups = array_combine(
			array_map(
				function ( QuestionGroup $qg ) {
					return $qg->id;
				},
				$question_groups
			),
			$question_groups
		);

		$group = $this->group;

		$registration_evaluation = $group->evaluate_registration();

		$token = JwtUtils::create_token( $competition->id, $group->id, $group->random_id );

		include 'views/group.php';
	}
}
