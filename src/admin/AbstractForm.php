<?php

namespace tuja\admin;

use Exception;
use tuja\data\store\CompetitionDao;
use tuja\data\store\FormDao;
use tuja\data\store\QuestionGroupDao;
use tuja\data\store\QuestionDao;

class AbstractForm {
	protected $form;
	protected $competition;
	protected $form_dao;
	protected $question_dao;
	protected $question_group_dao;

	public function __construct() {
		$this->competition_dao    = new CompetitionDao();
		$this->form_dao           = new FormDao();
		$this->question_dao       = new QuestionDao();
		$this->question_group_dao = new QuestionGroupDao();

		if ( isset( $_GET['tuja_question_group'] ) ) {
			$this->question_group = $this->question_group_dao->get( $_GET['tuja_question_group'] );
			$this->form           = $this->form_dao->get( $this->question_group->form_id );
			$this->competition    = $this->competition_dao->get( $this->form->competition_id );
		} elseif ( isset( $_GET['tuja_form'] ) ) {
			$this->form        = $this->form_dao->get( $_GET['tuja_form'] );
			$this->competition = $this->competition_dao->get( $this->form->competition_id );
		} elseif ( isset( $_GET['tuja_competition'] ) ) {
			$this->competition = $this->competition_dao->get( $_GET['tuja_competition'] );
		}

		if ( ! $this->competition ) {
			print 'Could not find competition';
			return;
		}
	}

	public function print_menu() {
		$current_view_name = $_GET['tuja_view'];

		$menu = BreadcrumbsMenu::create();

		//
		// First level
		//
		$groups_start_page_link = 'Competition' !== $current_view_name ? add_query_arg(
			array(
				'tuja_view'           => 'Competition',
				'tuja_competition'    => $this->competition->id,
				'tuja_form'           => null,
				'tuja_question_group' => null,
			)
		) : null;
		$menu->add(
			BreadcrumbsMenu::item( 'Formulär', $groups_start_page_link )
		);

		//
		// Second level
		//

		if ( 'Competition' !== $current_view_name ) {
			$forms_current = null;
			$forms_links   = array();
			$forms         = $this->form_dao->get_all_in_competition( $this->competition->id );
			foreach ( $forms as $form ) {
				if ( $form->id === $this->form->id ) {
					$forms_current = $form->name;
				} else {
					$link          = add_query_arg(
						array(
							'tuja_view'           => 'Form',
							'tuja_competition'    => $this->competition->id,
							'tuja_form'           => $form->id,
							'tuja_question_group' => null,
						)
					);
					$forms_links[] = BreadcrumbsMenu::item( $form->name, $link );
				}
			}
			$menu->add(
				BreadcrumbsMenu::item( $forms_current ),
				...$forms_links,
			);
		}

		//
		// Third level
		//

		if ( 'FormQuestions' === $current_view_name ) {
			$question_group_current = null;
			$question_group_links   = array();
			$question_groups        = $this->question_group_dao->get_all_in_form( intval( $_GET['tuja_form'] ) );
			foreach ( $question_groups as $question_group ) {
				if ( $question_group->id === $this->question_group->id ) {
					$question_group_current = $question_group->text ?? $question_group->id;
				} else {
					$link                   = add_query_arg(
						array(
							'tuja_view'           => 'FormQuestions',
							'tuja_competition'    => $this->competition->id,
							'tuja_form'           => $form->id,
							'tuja_question_group' => $question_group->id,
						)
					);
					$question_group_links[] = BreadcrumbsMenu::item( $question_group->text ?? $question_group->id, $link );
				}
			}

			$menu->add(
				BreadcrumbsMenu::item( $question_group_current ),
				...$question_group_links,
			);
		}
		print $menu->render();
	}
}
