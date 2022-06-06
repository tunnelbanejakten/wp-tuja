<?php

namespace tuja\admin;

class CompetitionSettings extends Competition {
	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		if ( $current_view_name !== 'CompetitionSettings' ) {
			$sub_page_current = null;
			$sub_page_links   = array();
			$items            = array(
				CompetitionSettingsBasic::class            => 'Namn och tid',
				CompetitionSettingsGroupCategories::class  => 'Gruppkategorier',
				CompetitionSettingsStrings::class          => 'Texter',
				CompetitionSettingsApp::class              => 'Appen',
				CompetitionSettingsFees::class             => 'Avgifter',
				CompetitionSettingsGroupLifecycle::class   => 'Livscykel för grupper',
				CompetitionSettingsMessageTemplates::class => 'Meddelandemallar',
			);
			foreach ( $items as $full_view_name => $title ) {
				$short_view_name = substr( $full_view_name, strrpos( $full_view_name, '\\' ) + 1 );
				if ( $short_view_name === $current_view_name ) {
					$sub_page_current = $title;
				} else {
					$link             = add_query_arg(
						array(
							'tuja_competition' => $this->competition->id,
							'tuja_view'        => $short_view_name,
						)
					);
					$sub_page_links[] = BreadcrumbsMenu::item( $title, $link );
				}
			}
			$menu->add(
				BreadcrumbsMenu::item( $sub_page_current ),
				...$sub_page_links,
			);
		}

		return $menu;
	}

	public function output() {
		$competition = $this->competition;

		include( 'views/competition-settings.php' );
	}
}
