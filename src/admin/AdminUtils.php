<?php

namespace tuja\admin;

use Exception;
use tuja\Admin;
use tuja\util\ImageManager;
use tuja\data\model\Group;
use tuja\data\model\Competition;
use tuja\util\fee\CompetingParticipantFeeCalculator;
use tuja\util\fee\PersonTypeFeeCalculator;
use tuja\util\fee\FixedFeeCalculator;

class AdminUtils {
	const INHERIT = 'inherit';

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

	public static function printTooltip( $message ) {
		echo self::tooltip( $message );
	}

	public static function tooltip( $message ) {
		return sprintf(
			'
				<span class="tooltip">
					<span class="tooltip-button">
						<span class="dashicons dashicons-info"></span>
					</span>
					<span class="tooltip-content tooltip-position-right">
						%s
					</span>
				</span>
			',
			$message
		);
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


	public static function get_fee_configuration_object( string $form_field_name ) {
		$fee_calculator_cfg = json_decode( stripslashes( @$_POST[ $form_field_name ] ?? '{}' ), true );
		if ( @$fee_calculator_cfg['type'] !== self::INHERIT ) {
			$fee_calculator = ( new \ReflectionClass( $fee_calculator_cfg['type'] ) )->newInstance();
			$fee_calculator->configure( $fee_calculator_cfg[ 'config_' . $fee_calculator_cfg['type'] ] );
			return $fee_calculator;
		} else {
			return null;
		}
	}

	public static function print_fee_configuration_form( $fee_calculator, string $target_field_name, bool $is_inherit_available ) {
		wp_enqueue_style( 'tuja-admin-payment-options', Admin::get_url() . '/assets/css/admin-payment-options.css' );

		$fee_calculators        = array(
			CompetingParticipantFeeCalculator::class => 'Betala per tävlande',
			PersonTypeFeeCalculator::class           => 'Betala beroende på roll',
			FixedFeeCalculator::class                => 'Fast avgift',
		);
		$fee_calculator_classes = array_keys( $fee_calculators );

		/**
		 * $jsoneditor_config will look something like this:
		 *
		 *  {
		 *    "type": "object",
		 *      "properties": {
		 *        "type": {
		 *          "title": "Avgiftsmodell",
		 *          "type": "string",
		 *          "default": "PersonTypeFeeCalculator",
		 *          "enum": [
		 *            "PersonTypeFeeCalculator",
		 *            "FixedFeeCalculator"
		 *          ]
		 *        },
		 *        ...
		 *        "config_FixedFeeCalculator": {
		 *          "type": "object",
		 *          "title": "Inst\u00e4llningar f\u00f6r FixedFeeCalculator",
		 *          "options": {
		 *            "dependencies": {
		 *              "type": "FixedFeeCalculator"
		 *            }
		 *          },
		 *          "properties": {
		 *            "fee": {
		 *              "title": "Avgift",
		 *              "type": "integer",
		 *              "format": "number"
		 *            }
		 *            ...
		 *          }
		 *        }
		 *      }
		 *    }
		 *  }
		 */
		$jsoneditor_config = array(
			'type'       => 'object',
			'required'   => array( 'type' ),
			'properties' => array_merge(
				array(
					'type' => array(
						'title'   => 'Avgiftsmodell',
						'type'    => 'string',
						'default' => $fee_calculator_classes[0],
						'enum'    => array_merge(
							$is_inherit_available ? array( self::INHERIT ) : array(),
							$fee_calculator_classes
						),
						'options' => array(
							'enum_titles' => array_merge(
								$is_inherit_available ? array( 'Ingen anpassning' ) : array(),
								array_values( $fee_calculators )
							),
						),
					),
				),
				array_combine(
					array_map(
						function ( $class_name ) {
							return 'config_' . $class_name;
						},
						$fee_calculator_classes
					),
					array_map(
						function ( $class_name ) use ( $fee_calculators ) {
							$header_schema = array(
								'type'    => 'object',
								'title'   => 'Inställningar för ' . $fee_calculators[ $class_name ],
								'options' => array(
									'dependencies' => array(
										'type' => $class_name,
									),
								),
							);

							$config_schema = ( ( new \ReflectionClass( $class_name ) )->newInstance() )->get_config_json_schema();

							return array_merge(
								$header_schema,
								$config_schema
							);
						},
						$fee_calculator_classes
					)
				)
			),
		);

		/**
		 * $default_values will look something like this:
		 *
		 *  {
		 *    "type": "PersonTypeFeeCalculator",
		 *    "config_PersonTypeFeeCalculator": {
		 *      "fee_leader": 0,
		 *      "fee_regular": 0,
		 *      "fee_supervisor": 0,
		 *      "fee_admin": 0
		 *    },
		 *    "config_FixedFeeCalculator": {
		 *      "fee": 0
		 *    }
		 *  }
		 */
		$is_inherit_selected = ! isset( $fee_calculator );
		$fee_calculator_fqn  = $is_inherit_selected ? '' : ( new \ReflectionClass( $fee_calculator ) )->getName();
		$default_values      = array_merge(
			array(
				'type' => $is_inherit_selected ? self::INHERIT : $fee_calculator_fqn,
			),
			array_combine(
				array_map(
					function ( $class_name ) {
						return 'config_' . $class_name;
					},
					$fee_calculator_classes
				),
				array_map(
					function ( $class_name ) {
						return ( ( new \ReflectionClass( $class_name ) )->newInstance() )->get_default_config();
					},
					$fee_calculator_classes
				)
			)
		);

		$stored_values = $is_inherit_selected ? array() : array(
			'config_' . $fee_calculator_fqn => $fee_calculator->get_config(),
		);

		$jsoneditor_values = array_merge(
			$default_values,
			$stored_values // Overrides any default values, including which fee calculator is actually used.
		);

		return sprintf(
			'<div class="tuja-admin-formgenerator-form tuja-admin-payment-options-form" 
				data-schema="%s" 
				data-values="%s" 
				data-field-id="%s"
				data-root-name="%s"></div>
			<input type="hidden" name="%s" id="%s" value="%s">',
			htmlentities( json_encode( $jsoneditor_config ) ),
			htmlentities( json_encode( $jsoneditor_values ) ),
			$target_field_name,
			"{$target_field_name}_temp",
			$target_field_name,
			$target_field_name,
			htmlentities( json_encode( $jsoneditor_values ) )
		);
	}

	public static function qr_code_button( $value ) {
		$id = uniqid();
		return sprintf(
			'
			<a title="QR-kod" href="#TB_inline?&width=300&height=300&inlineId=tuja-qr-code-viewer-%s" class="thickbox" data-qr-value="%s" data-target-id="tuja-qr-code-image-%s">Visa QR-kod</a>
			<span id="tuja-qr-code-viewer-%s" style="display: none"><span><img src="" id="tuja-qr-code-image-%s"></span></span>',
			$id,
			htmlentities( $value ),
			$id,
			$id,
			$id
		);
	}
}
