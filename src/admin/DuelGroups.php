<?php

namespace tuja\admin;

use tuja\data\model\DuelGroup;
use tuja\data\model\ValidationException;
use tuja\data\store\DuelDao;

class DuelGroups extends Duels {
	protected $duel_dao;

	public function __construct() {
		parent::__construct();
		$this->duel_dao = new DuelDao();
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
		}
	}


	public function output() {
		$this->handle_post();

		$competition = $this->competition;

		$duel_groups = $this->duel_dao->get_duels_by_competition( $competition->id, true );

		include 'views/duel-groups.php';
	}
}
