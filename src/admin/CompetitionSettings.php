<?php

namespace tuja\admin;

class CompetitionSettings extends Competition {
	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );
		return $this->add_static_menu(
			$menu,
			array(
				CompetitionSettings::class                 => 'Inställningar',
				CompetitionSettingsBasic::class            => 'Namn och tid',
				CompetitionSettingsGroupCategories::class  => 'Gruppkategorier',
				CompetitionSettingsStrings::class          => 'Texter',
				CompetitionSettingsApp::class              => 'Appen',
				CompetitionSettingsFees::class             => 'Avgifter',
				CompetitionSettingsGroupLifecycle::class   => 'Livscykel för grupper',
				CompetitionSettingsMessageTemplates::class => 'Meddelandemallar',
			)
		);
	}

	public function output() {
		$competition = $this->competition;

		include( 'views/competition-settings.php' );
	}
}
