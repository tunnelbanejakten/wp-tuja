<?php

namespace tuja\admin;

use Exception;
use tuja\util\ImageManager;
use tuja\data\model\Group;
use tuja\data\model\Competition;

class AdminUtils {
	private static $is_admin_mode = false;

	/**
	 * Prints an error message, with WP's default admin page style, based on an exception.
	 */
	public static function printException( Exception $ex ) {
		self::printError( $ex->getMessage() );
	}

	public static function printError( $message ) {
		printf(
			'<div class="notice notice-error is-dismissable" style="margin-left: 2px"><p><strong>%s: </strong>%s</p></div>',
			'Fel',
			$message
		);
	}

	public static function printSuccess( $message ) {
		printf( '<div class="notice notice-success is-dismissable" style="margin-left: 2px"><p>%s</p></div>', $message );
	}

	public static function getScoreCssClass( $score_percent ) {
		if ( $score_percent > 0.90 ) {
			return 'tuja-admin-review-autoscore-good';
		} elseif ( $score_percent < 0.10 ) {
			return 'tuja-admin-review-autoscore-poor';
		} else {
			return 'tuja-admin-review-autoscore-decent';
		}
	}

	public static function printTopMenu( $competition ) {
		printf( '<h1>%s</h1>', $competition->name );

		$menu_config = array(
			'Groups'     => 'Grupper',
			'Scoreboard' => 'Poängställning',
			'Review'     => 'Svar att rätta',
			'Messages'   => 'Meddelanden',
			'Reports'    => 'Rapporter',
		);
		if ( self::is_admin_mode() ) {
			$menu_config = array_merge(
				$menu_config,
				array(
					'Competition'         => 'Formulär',
					'Stations'            => 'Stationer',
					'Maps'                => 'Kartor',
					'CompetitionSettings' => 'Inställningar',
					'Shortcodes'          => 'Shortcodes',
					'CompetitionDelete'   => 'Rensa',
				)
			);
		}

		$menu = array();
		foreach ( $menu_config as $view => $label ) {
			$is_view_selected = sanitize_text_field( $_GET['tuja_view'] ) === $view;
			if ( $is_view_selected ) {
				$menu[] = sprintf( '<strong>%s</strong>', $label );
			} else {
				$menu[] = sprintf(
					'<a href="%s">%s</a>',
					add_query_arg(
						array(
							'tuja_competition' => $competition->id,
							'tuja_view'        => $view,
						)
					),
					$label
				);
			}
		}

		printf( '<nav class="tuja">%s</nav>', join( ' | ', $menu ) );
	}

	public static function get_image_thumbnails_html( $answer, $group_key = null ) {
		if ( is_array( $answer ) && isset( $answer[0] ) && ! is_array( $answer[0] ) && ! empty( $answer[0] ) ) {
			// Fix legacy format (JSON as string in array)
			$answer = json_decode( $answer[0], true );
			if ( $answer == null ) {
				return 'Syntaxfel!';
			}
		} else {
			if ( $answer == null ) {
				return '';
			}
		}

		if ( ! is_array( $answer['images'] ) ) {
			return 'Ingen lista med filnamn.';
		}

		if ( empty( $answer['images'][0] ) ) {
			return 'Inget filnamn.';
		}

		$image_manager = new ImageManager();
		$comment       = $answer['comment'] ?? '';
		$lines         = array_merge(
			array( sprintf( '<em>%s</em>', $comment ) ),
			array_map(
				function ( $image_id ) use ( $image_manager, $group_key ) {
					$thumbnail_image_url = $image_manager->get_resized_image_url(
						$image_id,
						ImageManager::DEFAULT_THUMBNAIL_PIXEL_COUNT,
						$group_key
					);

					if ( $thumbnail_image_url !== false ) {
						$large_image_url = $image_manager->get_resized_image_url(
							$image_id,
							ImageManager::DEFAULT_LARGE_PIXEL_COUNT,
							$group_key
						);

						$popup_id   = uniqid();
						$popup      = sprintf( '<div id="tuja-image-viewer-%s" style="display: none"><img src="%s" style="width: 100%%"></div>', $popup_id, $large_image_url );
						$popup_link = sprintf( '<a href="#TB_inline?&width=900&height=900&inlineId=tuja-image-viewer-%s" class="thickbox"><img src="%s"></a>', $popup_id, $thumbnail_image_url );
						return $popup_link . $popup;
					} else {
						return sprintf( 'Kan inte visa bild group-%s/%s', $group_key, $image_id );
					}

				},
				$answer['images']
			)
		);

		return join( '<br>', $lines );
	}

	public static function set_admin_mode( $is_admin ) {
		self::$is_admin_mode = $is_admin;
	}

	public static function is_admin_mode() {
		return self::$is_admin_mode;
	}

	public static function get_initial_group_status_selector( string $preselected_status, string $field_name ) {
		return join(
			'<br>',
			array_map(
				function ( $status ) use ( $preselected_status, $field_name ) {

					$status_descriptions = array(
						Group::STATUS_CREATED           => 'Inga meddelanden skickas ut per automatik.',
						Group::STATUS_AWAITING_APPROVAL => 'Bra om tävlingsledningen måste godkänna lag innan de får vara med. Automatiska meddelanden kan konfigureras.',
						Group::STATUS_ACCEPTED          => 'Bra om alla lag som anmäler sig får plats i tävlingen. Automatiska meddelanden kan konfigureras.',
					);

					$id = $field_name . '-' . $status;

					return sprintf(
						'<input type="radio" id="%s" name="%s" value="%s" %s/><label for="%s"><span class="tuja-admin-groupstatus tuja-admin-groupstatus-%s">%s</span> <small>%s</small></label>',
						$id,
						$field_name,
						$status,
						$status == ( $preselected_status ?: Group::DEFAULT_STATUS ) ? 'checked="checked"' : '',
						$id,
						$status,
						$status,
						@$status_descriptions[ $status ]
					);
				},
				Competition::allowed_initial_statuses()
			)
		);
	}
}
