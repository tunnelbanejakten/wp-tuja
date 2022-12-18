<?php

namespace tuja\admin;

use Exception;
use tuja\data\store\FormDao;
use tuja\frontend\router\FormInitiator;
use tuja\frontend\router\GroupCheckinInitiator;
use tuja\frontend\router\GroupEditorInitiator;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\frontend\router\GroupPeopleEditorInitiator;
use tuja\frontend\router\GroupSignupInitiator;
use tuja\frontend\router\PointsOverrideInitiator;
use tuja\util\AppUtils;

class GroupLinks extends Group {

	private $form_dao;

	public function __construct() {
		parent::__construct();

		$this->form_dao = new FormDao();
	}

	public function get_scripts(): array {
		return array(
			'admin-group-links.js',
			'qrious-4.0.2.min.js',
		);
	}

	public function output() {
		$group       = $this->group;
		$competition = $this->competition;

		$group_home_link          = GroupHomeInitiator::link( $group );
		$group_signup_link        = GroupSignupInitiator::link( $group );
		$group_people_editor_link = GroupPeopleEditorInitiator::link( $group );
		$group_editor_link        = GroupEditorInitiator::link( $group );
		$group_checkin_link       = GroupCheckinInitiator::link( $group );
		$app_link                 = AppUtils::group_link( $group );

		$is_crew_group     = $group->is_crew;
		$crew_signup_links = ! $is_crew_group ? array_map(
			function ( \tuja\data\model\Group $crew_group ) use ( $group ) {
				$link_url = GroupSignupInitiator::link( $crew_group, $group );
				return sprintf(
					'<tr><td>Anmäl funktionär till %s och ge bonus till %s:</td><td><a href="%s">%s</a></td><td>%s</td></tr>',
					$crew_group->name,
					$group->name,
					$link_url,
					$link_url,
					AdminUtils::qr_code_button( $link_url )
				);
			},
			array_filter(
				$this->group_dao->get_all_in_competition( $competition->id, false, null ),
				function ( \tuja\data\model\Group $group ) {
					return $group->is_crew;
				}
			)
		) : array();

		$group_form_links = array_map(
			function ( \tuja\data\model\Form $form ) use ( $group, $is_crew_group ) {
				if ( $is_crew_group ) {
					$link = PointsOverrideInitiator::link( $group, $form->id );
					return sprintf(
						'<tr><td>Länk för att rapportering in poäng för formulär %s:</td><td><a href="%s">%s</a></td><td>%s</td></tr>',
						$form->name,
						$link,
						$link,
						AdminUtils::qr_code_button( $link )
					);
				} else {
					$link = FormInitiator::link( $group, $form );
					return sprintf(
						'<tr><td>Länk för att svara på formulär %s:</td><td><a href="%s">%s</a></td><td>%s</td></tr>',
						$form->name,
						$link,
						$link,
						AdminUtils::qr_code_button( $link )
					);
				}
			},
			$this->form_dao->get_all_in_competition( $competition->id )
		);

		include 'views/group-links.php';
	}
}
