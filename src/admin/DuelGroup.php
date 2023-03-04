<?php

namespace tuja\admin;

use tuja\data\store\CompetitionDao;
use tuja\data\store\DuelDao;

class DuelGroup extends Duels {

	const ACTION_DELETE = 'delete';
	const ACTION_SAVE   = 'save';

	public function __construct() {
		parent::__construct();

		if ( isset( $_GET['tuja_duel_group'] ) ) {
			$this->duel_group = $this->duel_dao->get_duel_group( intval( $_GET['tuja_duel_group'] ) );
		}

		$this->assert_set( 'Duel group not found', $this->duel_group );
		$this->assert_same( 'Duel group needs to belong to competition', $this->duel_group->competition_id, $this->competition->id );
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		$duel_group_current = null;
		$duel_groups_links  = array();
		$duel_groups        = $this->duel_dao->get_duels_by_competition( $this->competition->id, true );
		foreach ( $duel_groups as $duel_group ) {
			$active = $duel_group->id === $this->duel_group->id;
			if ( $active ) {
				$duel_group_current = $duel_group->name;
			}
			$link                = add_query_arg(
				array(
					'tuja_view'       => 'DuelGroup',
					'tuja_duel_group' => $duel_group->id,
				)
			);
			$duel_groups_links[] = BreadcrumbsMenu::item( $duel_group->name, $link, $active );
		}
		$menu->add(
			BreadcrumbsMenu::item( $duel_group_current ),
			...$duel_groups_links,
		);

		return $menu;
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_duel_group_action'] ) ) {
			return true;
		}

		$action = @$_POST['tuja_duel_group_action'];
		if ( self::ACTION_SAVE === $action ) {
			$this->duel_group->name = @$_POST['tuja_duel_group_name'];

			$success = $this->duel_dao->update_duel_group( $this->duel_group );

			if ( $success ) {
				$this->duel_group = $this->duel_dao->get_duel_group( $this->duel_group->id );
				AdminUtils::printSuccess( 'Ändringar sparade.' );
			} else {
				AdminUtils::printError( 'Kunde inte spara.' );
			}
		} elseif ( self::ACTION_DELETE === $action ) {
			$success = ( $this->duel_dao->delete_duel_group( $this->duel_group->id ) === 1 );

			if ( $success ) {
				$back_url = add_query_arg(
					array(
						'tuja_competition' => $this->competition->id,
						'tuja_view'        => 'Duels',
					)
				);
				AdminUtils::printSuccess( sprintf( 'Duelgruppen har tagits bort. Vad sägs om att gå till <a href="%s">startsidan för dueller</a>?', $back_url ) );

				return false;
			} else {
				AdminUtils::printError( 'Kunde inte ta bort duellgruppen.' );
			}
		}
		return true;
	}

	public function output() {
		$is_station_available = $this->handle_post();

		$competition = $this->competition;
		$duel_group  = $this->duel_group;
		$back_url    = add_query_arg(
			array(
				'tuja_competition' => $competition->id,
				'tuja_view'        => 'Duels',
			)
		);

		if ( $is_station_available ) {
			include 'views/duel-group.php';
		}
	}
}
