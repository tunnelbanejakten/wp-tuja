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

class GroupLinks extends AbstractGroup {

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

		$is_crew_group    = $group->get_category()->get_rules()->is_crew();
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
