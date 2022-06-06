<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\ValidationException;
use tuja\data\model\Competition;
use tuja\data\store\CompetitionDao;
use tuja\util\Strings;
use tuja\util\fee\CompetingParticipantFeeCalculator;
use tuja\util\fee\PersonTypeFeeCalculator;
use tuja\util\fee\FixedFeeCalculator;
use tuja\util\fee\GroupFeeCalculator;
use tuja\util\paymentoption\OtherPaymentOption;
use tuja\util\paymentoption\PaymentOption;
use tuja\util\paymentoption\SwishPaymentOption;
use tuja\data\model\GroupCategory;
use tuja\data\model\Group;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;

class CompetitionSettingsFees extends CompetitionSettings {
	const FIELD_SEPARATOR = '__';

	public function __construct() {
		parent::__construct();

		$this->group_category_dao = new GroupCategoryDao();
		$this->group_dao          = new GroupDao();
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_competition_settings_action'] ) ) {
			return;
		}

		if ( $_POST['tuja_competition_settings_action'] === 'save' ) {
			$this->competition_settings_save( $this->competition );

			$categories = $this->group_category_dao->get_all_in_competition( $this->competition->id );
			array_walk(
				$categories,
				function ( GroupCategory $group_category ) {
					$this->group_category_settings_save( $group_category );
				}
			);

			$groups = $this->group_dao->get_all_in_competition( $this->competition->id );
			array_walk(
				$groups,
				function ( Group $group ) {
					$this->group_settings_save( $group );
				}
			);
		}
	}

	private function group_category_settings_save( GroupCategory $category ) {
		try {
			$id                       = $category->id;
			$new_value                = AdminUtils::get_fee_configuration_object( $this->list_item_field_name( 'tuja_category_fee', $id ) );
			$category->fee_calculator = $new_value;

			$this->group_category_dao->update( $category );
		} catch ( ValidationException $e ) {
			AdminUtils::printException( $e );
		} catch ( Exception $e ) {
			AdminUtils::printException( $e );
		}
	}

	private function group_settings_save( Group $group ) {
		try {
			$id                    = $group->id;
			$new_value             = AdminUtils::get_fee_configuration_object( $this->list_item_field_name( 'tuja_group_fee', $id ) );
			$group->fee_calculator = $new_value;

			$this->group_dao->update( $group );
		} catch ( ValidationException $e ) {
			AdminUtils::printException( $e );
		} catch ( Exception $e ) {
			AdminUtils::printException( $e );
		}
	}

	public function competition_settings_save( Competition $competition ) {
		try {
			// Fee calculator
			$competition->fee_calculator = AdminUtils::get_fee_configuration_object( 'tuja_competition_fee' );

			// Payment methods
			$payment_options_cfg          = json_decode( stripslashes( $_POST['tuja_payment_options'] ), true );
			$enabled_payment_options_cfg  = array_filter(
				$payment_options_cfg,
				function ( $cfg ) {
					return $cfg['enabled'] === true;
				}
			);
			$competition->payment_options = array_map(
				function ( string $key, $config ) {
					$payment_option = ( new \ReflectionClass( $key ) )->newInstance();
					unset( $config['enabled'] );
					$payment_option->configure( $config );

					return $payment_option;
				},
				array_keys( $enabled_payment_options_cfg ),
				array_values( $enabled_payment_options_cfg )
			);

			$this->competition_dao->update( $competition );
		} catch ( Exception $e ) {
			// TODO: Reuse this exception handling elsewhere?
			AdminUtils::printException( $e );
		}
	}

	public function get_scripts(): array {
		return array(
			'admin-formgenerator.js',
			'jsoneditor.min.js',
			'admin-competition-fees.js',
		);
	}

	public function output() {
		$this->handle_post();

		$competition = $this->competition_dao->get( $_GET['tuja_competition'] );

		$category_dao = new GroupCategoryDao();
		$group_dao    = new GroupDao();

		$back_url = add_query_arg(
			array(
				'tuja_competition' => $competition->id,
				'tuja_view'        => 'CompetitionSettings',
			)
		);

		include( 'views/competition-settings-fees.php' );
	}

	public function print_group_fee_configuration_form( Competition $competition ) {
		return AdminUtils::print_fee_configuration_form(
			$competition->fee_calculator,
			'tuja_competition_fee',
			false
		);
	}

	public function print_group_category_fee_override_configuration_form( GroupCategory $category ) {
		return AdminUtils::print_fee_configuration_form(
			$category->fee_calculator,
			$this->list_item_field_name( 'tuja_category_fee', $category->id ),
			true
		);
	}

	public function print_group_fee_override_configuration_form( Group $group ) {
		return AdminUtils::print_fee_configuration_form(
			$group->fee_calculator,
			$this->list_item_field_name( 'tuja_group_fee', $group->id ),
			true
		);
	}

	private function list_item_field_name( $list_name, $id ) {
		return join( self::FIELD_SEPARATOR, array( $list_name, $id ) );
	}

	public function print_payment_options_configuration_form( Competition $competition ) {

		$payment_option_classes = array( SwishPaymentOption::class, OtherPaymentOption::class );

		$jsoneditor_config = array(
			'type'       => 'object',
			'properties' =>
				array_combine(
					$payment_option_classes,
					array_map(
						function ( $class_name ) {
							return
							array(
								'type'       => 'object',
								'title'      => Strings::get( 'groups_payment.' . strtolower( ( new \ReflectionClass( $class_name ) )->getShortName() ) . '.header' ),
								'properties' => array_merge(
									array(
										'enabled' => array(
											'title'  => 'Visa detta betalningsalternativ för lagen',
											'type'   => 'boolean',
											'format' => 'checkbox',
										),
									),
									( ( new \ReflectionClass( $class_name ) )->newInstance() )->get_config_json_schema()
								),
							);
						},
						$payment_option_classes
					)
				),
		);

		$default_values =
			array_combine(
				$payment_option_classes,
				array_map(
					function ( $class_name ) {
						return array_merge( array( 'enabled' => false ), ( ( new \ReflectionClass( $class_name ) )->newInstance() )->get_default_config() );
					},
					$payment_option_classes
				)
			);

		$stored_values = array_combine(
			array_map(
				function ( PaymentOption $payment_option ) {
					return ( new \ReflectionClass( $payment_option ) )->getName();
				},
				$competition->payment_options
			),
			array_map(
				function ( PaymentOption $payment_option ) {
					return array_merge( array( 'enabled' => true ), $payment_option->get_config() );
				},
				$competition->payment_options
			)
		);

		$jsoneditor_values = array_merge(
			$default_values,
			$stored_values // Overrides any default values, including which fee calculator is actually used.
		);

		return sprintf(
			'
				<div class="tuja-admin-formgenerator-form" 
					data-schema="%s" 
					data-values="%s" 
					data-field-id="tuja_payment_options"
					data-root-name="tuja_payment_options"></div>',
			htmlentities( json_encode( $jsoneditor_config ) ),
			htmlentities( json_encode( $jsoneditor_values ) )
		);
	}
}
