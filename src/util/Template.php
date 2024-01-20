<?php

namespace tuja\util;

use DateTime;
use tuja\controller\PaymentsController;
use tuja\data\model\Competition;
use tuja\data\model\Group;
use tuja\data\model\Marker;
use tuja\data\model\Person;
use tuja\data\store\GroupDao;
use tuja\data\store\MapDao;
use tuja\data\store\MarkerDao;
use tuja\data\store\PaymentDao;
use tuja\frontend\router\CompetitionSignupInitiator;
use tuja\frontend\router\GroupCancelSignupInitiator;
use tuja\frontend\router\GroupCheckinInitiator;
use tuja\frontend\router\GroupEditorInitiator;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\frontend\router\GroupPaymentInitiator;
use tuja\frontend\router\GroupPeopleEditorInitiator;
use tuja\frontend\router\GroupSignupInitiator;
use tuja\frontend\router\GroupStatusInitiator;
use tuja\frontend\router\GroupTicketsInitiator;
use tuja\frontend\router\PersonEditorInitiator;
use tuja\frontend\router\ReportPointsInitiator;
use tuja\util\formattedtext\FormattedText;
use tuja\util\rules\RuleResult;

class Template {
	const TYPE_PLAIN_TEXT     = 'PLAIN_TEXT';
	const TYPE_HTML           = 'HTML';
	const TYPE_WP_EDITOR_HTML = 'WP_EDITOR_HTML';
	const TYPE_MARKDOWN       = 'MARKDOWN';

	private $content;
	private $content_type;

	private function __construct( $content, string $content_type ) {
		$this->content      = $content;
		$this->content_type = $content_type;
	}

	public function render( $parameters = array() ) {
		$rendered_content = $this->content;
		foreach ( $parameters as $name => $value ) {
			$rendered_content = str_replace( '{{' . $name . '}}', $value, $rendered_content );
		}
		switch ( $this->content_type ) {
			case self::TYPE_WP_EDITOR_HTML:
				return wpautop( $rendered_content );
			case self::TYPE_MARKDOWN:
				return ( new FormattedText() )->parse( $rendered_content );
			case self::TYPE_PLAIN_TEXT:
			case self::TYPE_HTML:
			default:
				return $rendered_content;
		}
	}

	public static function params_template_registration_evaluation( array $issues ): array {
		return array(
			'list_of_messages' => join(
				"\n",
				array_map(
					function ( RuleResult $issue ) {
						return sprintf( '- %s. %s', $issue->rule_name, $issue->details );
					},
					$issues
				)
			),
		);
	}

	public function get_variables() {
		$variables = array();
		preg_match_all( '/\{\{([a-zA-Z_]+)\}\}/', $this->content, $variables );

		return array_unique( $variables[1] );
	}

	public static function person_parameters( Person $person, Group $group ) {
		return array(
			'person_key'                => $person->random_id,
			'person_name'               => $person->name,
			'person_phone'              => $person->phone,
			'person_email'              => $person->email,
			'person_food'               => $person->food,
			'person_pno'                => $person->pno,
			'person_edit_link'          => PersonEditorInitiator::link( $group, $person ),
			'person_report_points_link' => ReportPointsInitiator::link_all( $person ),
		);
	}

	public static function group_parameters( Group $group, array $referral_signup_groups = array() ) {
		$evaluation_result = $group->evaluate_registration();

		$group_map_name        = 'Ni har ännu inte tilldelats en karta. Kontakta Kundtjänst.';
		$group_map_start_coord = '?';
		$group_map_start_label = '?';
		if ( isset( $group->map_id ) ) {
			// Get map name.
			$map_dao        = new MapDao();
			$map            = $map_dao->get( $group->map_id );
			$group_map_name = $map->name;

			// Get start location.
			$marker_dao   = new MarkerDao();
			$markers      = $marker_dao->get_all_on_map( $group->map_id );
			$start_marker = current(
				array_filter(
					$markers,
					function( Marker $marker ) {
						return $marker->type === Marker::MARKER_TYPE_START;
					}
				)
			);
			if ( $start_marker !== false ) {
				$group_map_start_coord = sprintf( '%s, %s', $start_marker->gps_coord_lat, $start_marker->gps_coord_long );
				$group_map_start_label = $start_marker->name;
			}
		}

		$referral_links = array();
		foreach ( $referral_signup_groups as $referral_signup_group ) {
			$key                    = sprintf( 'group_%s_referral_signup_link', preg_replace( '/[^a-z0-9]/', '', strtolower( $referral_signup_group->name ) ) );
			$referral_links[ $key ] = GroupSignupInitiator::link( $referral_signup_group, $group );
		}

		$payment_dao           = new PaymentDao();
		$payments_controller   = new PaymentsController( $group->competition_id );
		list ($fee, $fee_paid) = $payments_controller->group_fee_status( $group, $payment_dao->get_group_payments_by_group( $group ), new DateTime() );

		return array_merge(
			$referral_links,
			array(
				'group_name'                             => $group->name,
				'group_key'                              => $group->random_id,
				'group_home_link'                        => GroupHomeInitiator::link( $group ),
				'group_payment_link'                     => GroupPaymentInitiator::link( $group ),
				'group_app_link'                         => AppUtils::group_link( $group ),
				'group_app_checkin_link'                 => AppUtils::group_checkin_link( $group ),
				'group_app_auth_code'                    => $group->auth_code,
				'group_map_name'                         => $group_map_name,
				'group_map_start_coord'                  => $group_map_start_coord,
				'group_map_start_label'                  => $group_map_start_label,
				'group_edit_link'                        => GroupEditorInitiator::link( $group ),
				'group_people_edit_link'                 => GroupPeopleEditorInitiator::link( $group ),
				'group_signup_link'                      => GroupSignupInitiator::link( $group ),
				'group_status_link'                      => GroupStatusInitiator::link( $group ),
				'group_tickets_link'                     => GroupTicketsInitiator::link( $group ),
				'group_checkin_link'                     => GroupCheckinInitiator::link( $group ),
				'group_cancel_signup_link'               => GroupCancelSignupInitiator::link( $group ),
				'group_registration_evaluation_warnings' => self::group_parameter_registration_issues(
					'template.registration_evaluation.warnings.label',
					$evaluation_result,
					RuleResult::WARNING
				),
				'group_registration_evaluation_errors'   => self::group_parameter_registration_issues(
					'template.registration_evaluation.errors.label',
					$evaluation_result,
					RuleResult::BLOCKER
				),
				'group_fee'                              => number_format_i18n( $fee ),
				'group_fee_debt'                         => number_format_i18n( $fee - $fee_paid ),
				'group_fee_paid'                         => number_format_i18n( $fee_paid ),
			)
		);
	}

	public static function competition_parameters( Competition $competition ) {
		return array(
			'competition_signup_link' => CompetitionSignupInitiator::link( $competition ),
		);
	}

	private static function group_parameter_registration_issues( $template_key, array $evaluation_result, $status ) {
		$issues = array_filter(
			$evaluation_result,
			function ( $issue ) use ( $status ) {
				return $issue->status === $status;
			}
		);

		if ( empty( $issues ) ) {
			return '';
		}

		return Strings::get( $template_key, self::params_template_registration_evaluation( $issues ) );
	}

	public static function site_parameters() {
		return array(
			'base_url'         => get_site_url(),
			'admin_email'      => get_option( 'admin_email' ),
			'admin_email_link' => sprintf( '<a href="mailto:%s">%s</a>', get_bloginfo( 'admin_email' ), get_bloginfo( 'admin_email' ) ),
		);
	}

	public static function string( $content, string $content_type = self::TYPE_MARKDOWN ) {
		return new Template( $content, $content_type );
	}

	public static function file( $path ) {
		if ( file_exists( $path ) ) {
			return new Template( file_get_contents( $path ), self::TYPE_HTML );
		} elseif ( file_exists( __DIR__ . '/' . $path ) ) {
			return new Template( file_get_contents( __DIR__ . '/' . $path ), self::TYPE_HTML );
		} elseif ( file_exists( __DIR__ . '/../' . $path ) ) {
			return new Template( file_get_contents( __DIR__ . '/../' . $path ), self::TYPE_HTML );
		}
	}
}
