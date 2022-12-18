<?php

namespace tuja\util;

use tuja\data\model\Competition;
use tuja\data\model\Group;
use tuja\data\model\Marker;
use tuja\data\model\Person;
use tuja\data\store\MapDao;
use tuja\data\store\MarkerDao;
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
	private $content;

	private function __construct( $content ) {
		$this->content = $content;
	}

	public function render( $parameters = array(), $is_markdown = false ) {
		$rendered_content = $this->content;
		foreach ( $parameters as $name => $value ) {
			$rendered_content = str_replace( '{{' . $name . '}}', $value, $rendered_content );
		}
		if ( $is_markdown ) {
			$markdown_parser = new FormattedText();

			return $markdown_parser->parse( $rendered_content );
		} else {
			return $rendered_content;
		}
	}

	public static function params_template_registration_evaluation( array $issues ): array {
		return [
			'list_of_messages' => join(
				"\n",
				array_map(
					function ( RuleResult $issue ) {
						return sprintf( '- %s. %s', $issue->rule_name, $issue->details );
					},
					$issues ) )
		];
	}

	public function get_variables() {
		$variables = [];
		preg_match_all( '/\{\{([a-zA-Z_]+)\}\}/', $this->content, $variables );

		return array_unique( $variables[1] );
	}

	public static function person_parameters( Person $person, Group $group ) {
		return [
			'person_key'                => $person->random_id,
			'person_name'               => $person->name,
			'person_phone'              => $person->phone,
			'person_email'              => $person->email,
			'person_food'               => $person->food,
			'person_pno'                => $person->pno,
			'person_edit_link'          => PersonEditorInitiator::link( $group, $person ),
			'person_report_points_link' => ReportPointsInitiator::link_all( $person ),
		];
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

		return array_merge(
			$referral_links,
			array(
			'group_name'                             => $group->name,
			'group_key'                              => $group->random_id,
			'group_home_link'                        => GroupHomeInitiator::link( $group ),
			'group_payment_link'                     => GroupPaymentInitiator::link( $group ),
			'group_app_link'                         => AppUtils::group_link( $group ),
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
			)
		);
	}

	public static function competition_parameters( Competition $competition ) {
		return [
			'competition_signup_link' => CompetitionSignupInitiator::link( $competition )
		];
	}

	private static function group_parameter_registration_issues( $template_key, array $evaluation_result, $status ) {
		$issues = array_filter(
			$evaluation_result,
			function ( $issue ) use ( $status ) {
				return $issue->status === $status;
			} );

		if ( empty( $issues ) ) {
			return '';
		}

		return Strings::get( $template_key, self::params_template_registration_evaluation( $issues ) );
	}

	public static function site_parameters() {
		return [
			'base_url'    => get_site_url(),
			'admin_email' => get_option( 'admin_email' ),
			'admin_email_link' => sprintf( '<a href="mailto:%s">%s</a>', get_bloginfo( 'admin_email' ), get_bloginfo( 'admin_email' ) )
		];
	}

	public static function string( $content ) {
		return new Template( $content );
	}

	public static function file( $path ) {
		if ( file_exists( $path ) ) {
			return new Template( file_get_contents( $path ) );
		} elseif ( file_exists( __DIR__ . '/' . $path ) ) {
			return new Template( file_get_contents( __DIR__ . '/' . $path ) );
		} elseif ( file_exists( __DIR__ . '/../' . $path ) ) {
			return new Template( file_get_contents( __DIR__ . '/../' . $path ) );
		}
	}
}