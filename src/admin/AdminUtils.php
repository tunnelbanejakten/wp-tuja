<?php

namespace tuja\admin;

use Exception;

class AdminUtils
{
    /**
     * Prints an error message, with WP's default admin page style, based on an exception.
     */
    public static function printException(Exception $ex) {
        self::printError($ex->getMessage());
	}

	public static function printError($message) {
		printf('<div class="notice notice-error is-dismissable" style="margin-left: 2px"><p><strong>%s: </strong>%s</p></div>',
            'Oj, något blev fel',
            $message);
	}

	public static function printSuccess($message) {
		printf('<div class="notice notice-success is-dismissable" style="margin-left: 2px"><p>%s</p></div>', $message);
	}

	public static function getScoreCssClass( $score_percent ) {
		if ( $score_percent > 0.90 ) {
			return 'tuja-admin-review-autoscore-good';
		} else if ( $score_percent < 0.10 ) {
			return 'tuja-admin-review-autoscore-poor';
		} else {
			return 'tuja-admin-review-autoscore-decent';
		}
	}

	public static function printTopMenu( $competition ) {
		printf( '<h1>%s</h1>', $competition->name );

		$menu_config = [
			'Competition'         => 'Start',
			'Groups'              => 'Grupper',
			'Scoreboard'          => 'Poängställning',
			'CompetitionSettings' => 'Inställningar',
			'Review'              => 'Svar att rätta',
			'Messages'            => 'Meddelanden'
		];

		printf( '<nav class="tuja">%s</nav>',
			join( ' | ', array_map(
				function ( $view, $label ) {
					$is_view_selected = sanitize_text_field( $_GET['tuja_view'] ) === $view;

					return $is_view_selected
						? sprintf( '<strong>%s</strong>', $label )
						: sprintf( '<a href="%s">%s</a>',
							add_query_arg( array(
								'tuja_competition' => $_GET['tuja_competition'],
								'tuja_view'        => $view
							) ),
							$label );
				},
				array_keys( $menu_config ),
				array_values( $menu_config ) ) ) );

	}
}