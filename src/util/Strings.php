<?php


namespace tuja\util;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\Person;
use tuja\data\store\StringsDao;
use tuja\frontend\CompetitionSignup;
use tuja\frontend\GroupCheckin;
use tuja\frontend\GroupPeopleEditor;
use tuja\util\rules\RuleResult;

class Strings {
	private static $final_list = null;
	private static $default_list = null;
	private static $override_list = null;

	private static $custom_settings = [];

	public static function init( int $competition_id, bool $force = false ) {
		if ( self::$default_list == null || $force ) {
			self::$default_list = parse_ini_file( __DIR__ . '/strings.ini' );
			if ( $competition_id > 0 ) {
				self::$override_list = ( new StringsDao() )->get_all( $competition_id );
			}
			self::$final_list = array_merge( self::$default_list ?: [], self::$override_list ?: [] );
			ksort( self::$final_list );

			$group_people_editor_strings = array_combine(
				array_map( function ( string $type ) {
					return 'group_people_editor.' . $type . '.description';
				}, Person::PERSON_TYPES ),
				array_map( function ( string $type ) {
					return [
						'is_markdown'    => true,
						'get_parameters' => function () {
							return GroupPeopleEditor::params_section_description( GroupCategory::sample() );
						}
					];
				}, Person::PERSON_TYPES ) );

			$other_strings = [
				'checkin.yes.body_text'                                    => [
					'is_markdown'    => true,
					'get_parameters' => function () {
						return GroupCheckin::params_body_text( Group::sample() );
					}
				],
				'checkin.no.body_text'                                     => [
					'is_markdown'    => true,
					'get_parameters' => function () {
						return GroupCheckin::params_body_text( Group::sample() );
					}
				],
				'competition_signup.submitted.awaiting_approval.body_text' => [
					'is_markdown'    => true,
					'get_parameters' => function () {
						return CompetitionSignup::params_awaiting_approval( Group::sample() );
					}
				],
				'competition_signup.submitted.accepted.body_text'          => [
					'is_markdown'    => true,
					'get_parameters' => function () {
						return CompetitionSignup::params_accepted( Group::sample() );
					}
				],
				'competition_signup.intro.body_text'                       => [
					'is_markdown'    => true,
					'get_parameters' => function () {
						return [];
					}
				],
				'competition_signup.fineprint.body_text'                   => [
					'is_markdown'    => true,
					'get_parameters' => function () {
						return [];
					}
				],
				'template.registration_evaluation.warnings.label'          => [
					'is_markdown'    => true,
					'get_parameters' => function () {
						return Template::params_template_registration_evaluation( [
							new RuleResult( 'Regel A', RuleResult::WARNING, 'Något är inte som det ska.' )
						] );
					}
				],
				'template.registration_evaluation.errors.label'            => [
					'is_markdown'    => true,
					'get_parameters' => function () {
						return Template::params_template_registration_evaluation( [
							new RuleResult( 'Regel A', RuleResult::BLOCKER, 'Något är inte som det ska.' )
						] );
					}
				]
			];

			self::$custom_settings = array_merge( $other_strings, $group_people_editor_strings );
		}
	}

	public static function is_default_value( string $key, string $value ) {
		return $value == @self::$default_list[ $key ];
	}

	public static function is_markdown( string $key ): bool {
		if ( self::$final_list == null ) {
			throw new Exception( 'Strings::init() has not been called.' );
		}

		return @self::$custom_settings[ $key ] && @self::$custom_settings[ $key ]['is_markdown'] === true;
	}

	public static function get_sample_template_parameters( string $key ): array {
		if ( self::$final_list == null ) {
			throw new Exception( 'Strings::init() has not been called.' );
		}
		$parameter_function = @self::$custom_settings[ $key ]
			? @self::$custom_settings[ $key ]['get_parameters']
			: null;

		return $parameter_function
			? $parameter_function()
			: [];
	}

	public static function get( string $key, ...$args ): string {
		if ( self::$final_list == null ) {
			throw new Exception( 'Strings::init() has not been called.' );
		}

		$value = @self::$final_list[ $key ];
		if ( ! isset( $value ) ) {
			return "[$key]";
		}

		$template_settings = @self::$custom_settings[ $key ];
		if ( isset( $template_settings ) ) {
			if ( count( $args ) > 1 ) {
				throw new Exception( 'Parameters must be specified as array, not list of arguments.' );
			}

			return Template::string( $value )->render( @$args[0] ?: [], self::is_markdown( $key ) );
		} else {
			return sprintf( $value, ...$args );
		}


	}

	public static function get_list() {
		if ( self::$final_list == null ) {
			throw new Exception( 'Strings::init() has not been called.' );
		}

		return self::$final_list;
	}

}