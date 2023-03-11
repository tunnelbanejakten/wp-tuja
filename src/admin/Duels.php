<?php

namespace tuja\admin;

use Error;
use Exception;
use tuja\controller\DuelsController;
use tuja\data\model\DuelGroup;
use tuja\data\model\Group;
use tuja\data\model\ValidationException;
use tuja\data\store\DuelDao;
use tuja\data\store\GroupDao;

class Duels extends Competition {
	const FIELD_VALUE_SEP = ' ';

	const GROUP_SET_ALL         = 'all';
	const GROUP_SET_NOT_INVITED = 'not_invited';

	protected $duel_dao;
	protected $group_dao;

	public function __construct() {
		parent::__construct();
		$this->duel_dao  = new DuelDao();
		$this->group_dao = new GroupDao();
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		return $this->add_static_menu(
			$menu,
			array(
				Duels::class => array( 'Dueller', null ),
			)
		);
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_action'] == 'create_duel_group' ) {
			$props                        = new DuelGroup();
			$props->name                  = $_POST['tuja_duel_group_name'];
			$props->competition_id        = $this->competition->id;
			$props->link_form_question_id = null;
			try {
				$new_map_id = $this->duel_dao->create_duel_group( $props );
				if ( $new_map_id !== false ) {
					AdminUtils::printSuccess(
						sprintf(
							'<span id="tuja_duel_group_create_result" data-map-id="%s">Duellgruppen %s har lagts till.</span>',
							$new_map_id,
							$props->name
						)
					);
				}
			} catch ( ValidationException $e ) {
				AdminUtils::printException( $e );
			}
		} elseif ( $_POST['tuja_action'] == 'create_duels' ) {
			$min_duel_participant_count = intval( $_POST['tuja_min_duel_participant_count'] );

			( new DuelsController( $this->competition ) )->generate_invites( $min_duel_participant_count );
		}
	}


	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		$duel_groups = $this->duel_dao->get_duels_by_competition( $competition->id );

		include 'views/duels.php';
	}
}
