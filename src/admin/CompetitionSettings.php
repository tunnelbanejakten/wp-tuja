<?php

namespace tuja\admin;

class CompetitionSettings extends Competition {
	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );
		return $this->add_static_menu(
			$menu,
			array(
				CompetitionSettingsBasic::class            => array( 'Namn och tid', null ),
				CompetitionSettingsGroupCategories::class  => array( 'Gruppkategorier', null ),
				CompetitionSettingsStrings::class          => array( 'Texter', null ),
				CompetitionSettingsApp::class              => array( 'Appen', null ),
				CompetitionSettingsFees::class             => array( 'Avgifter', null ),
				CompetitionSettingsGroupLifecycle::class   => array( 'Livscykel för grupper', null ),
				CompetitionSettingsMessageTemplates::class => array( 'Meddelandemallar', null ),
			)
		);
	}

	public function output() {
		$competition = $this->competition;

		include( 'views/competition-settings.php' );
	}
}
