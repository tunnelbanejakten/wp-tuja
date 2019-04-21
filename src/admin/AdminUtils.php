<?php

namespace tuja\admin;

use Exception;
use tuja\util\ImageManager;

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
            'Fel',
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
			'Messages'            => 'Meddelanden',
			'Shortcodes'          => 'Shortcodes'
		];

		$menu = array();
		foreach($menu_config as $view => $label) {
			$is_view_selected = sanitize_text_field( $_GET['tuja_view'] ) === $view;
			if($is_view_selected) {
				$menu[] = sprintf( '<strong>%s</strong>', $label );
			} else {
				$menu[] = sprintf( '<a href="%s">%s</a>', add_query_arg( array('tuja_competition' => $competition->id, 'tuja_view' => $view ) ), $label );
			}
		}

		printf( '<nav class="tuja">%s</nav>', join( ' | ', $menu ) );
	}

	public static function get_image_thumbnails_html( $answer, $group_key = null ) {
		$answer = json_decode($answer, true);
		if (empty($answer['images'])) {
			return '';
		}

		$image_manager = new ImageManager();

		return join( array_map( function ( $image_id ) use ( $image_manager, $group_key ) {
			$resized_image_url = $image_manager->get_resized_image_url( $image_id, 200 * 200, $group_key );

			// TODO: Show fullsize image in modal popup when clicking image (see https://codex.wordpress.org/ThickBox)
			return $resized_image_url ? sprintf( '<img src="%s">', $resized_image_url ) : 'Kan inte visa bild.';
		}, $answer['images'] ) );
	}
}