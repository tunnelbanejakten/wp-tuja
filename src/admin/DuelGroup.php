<?php

namespace tuja\admin;

use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\question\TextQuestion;
use tuja\data\store\CompetitionDao;
use tuja\data\store\DuelDao;
use tuja\data\store\QuestionDao;

class DuelGroup extends DuelGroups {

	const MAGIC_NUMBER_NO_LINKING = -42;

	const FIELD_DUEL_GROUP_NAME       = 'tuja_duel_group_name';
	const FIELD_LINK_FORM_QUESTION_ID = 'tuja_link_form_question_id';

	const ACTION_DELETE = 'delete';
	const ACTION_SAVE   = 'save';

	private $question_dao;
	private $duel_group;

	public function __construct() {
		parent::__construct();

		if ( isset( $_GET['tuja_duel_group'] ) ) {
			$this->duel_group = $this->duel_dao->get_duel_group( intval( $_GET['tuja_duel_group'] ) );
		}

		$this->question_dao = new QuestionDao();

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

		$action = $_POST['tuja_duel_group_action'];
		if ( self::ACTION_SAVE === $action ) {
			$this->duel_group->name                  = $_POST[ self::FIELD_DUEL_GROUP_NAME ];
			$link_form_question_id                   = intval( $_POST[ self::FIELD_LINK_FORM_QUESTION_ID ] );
			$this->duel_group->link_form_question_id = self::MAGIC_NUMBER_NO_LINKING === $link_form_question_id ? null : $link_form_question_id;

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

		$pseudo_question_no_linking = new TextQuestion( null, '(Koppla inte)', null, self::MAGIC_NUMBER_NO_LINKING );

		$questions = array_merge(
			array( $pseudo_question_no_linking ),
			$this->question_dao->get_all_in_competition( $competition->id )
		);

		$selected_question_id = $duel_group->link_form_question_id ?? self::MAGIC_NUMBER_NO_LINKING;
		$questions_dropdown   = sprintf(
			'<select size="1" name="%s">%s</select>',
			self::FIELD_LINK_FORM_QUESTION_ID,
			join(
				array_map(
					function ( AbstractQuestion $question ) use ( $selected_question_id ) {
						return sprintf(
							'<option value="%s" %s>%s %s</option>',
							$question->id,
							$selected_question_id === $question->id ? 'selected="selected"' : '',
							$question->name,
							substr( $question->text, 0, 50 )
						);
					},
					$questions
				)
			)
		);

		if ( $is_station_available ) {
			include 'views/duel-group.php';
		}
	}
}
