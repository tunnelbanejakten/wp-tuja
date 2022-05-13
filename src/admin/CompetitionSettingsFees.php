<?php

namespace tuja\admin;

use Exception;
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

class CompetitionSettingsFees {
	const FIELD_SEPARATOR = '__';

	public function handle_post() {
		if ( ! isset( $_POST['tuja_competition_settings_action'] ) ) {
			return;
		}

		$competition_dao = new CompetitionDao();
		$competition     = $competition_dao->get( $_GET['tuja_competition'] );

		if ( ! $competition ) {
			throw new Exception( 'Could not find competition' );
		}

		if ( $_POST['tuja_competition_settings_action'] === 'save' ) {
			$this->competition_settings_save( $competition );
		}
	}

	public function competition_settings_save( Competition $competition ) {
		try {
			// Fee calculator
			$competition->fee_calculator = AdminUtils::get_fee_configuration_object('tuja_competition_settings_fee_calculator');

			// Payment methods
			$payment_options_cfg          = json_decode( stripslashes( $_POST['tuja_competition_settings_payment_options'] ), true );
			$enabled_payment_options_cfg  = array_filter( $payment_options_cfg, function ( $cfg ) {
				return $cfg['enabled'] === true;
			} );
			$competition->payment_options = array_map( function ( string $key, $config ) {
				$payment_option = ( new \ReflectionClass( $key ) )->newInstance();
				unset( $config['enabled'] );
				$payment_option->configure( $config );

				return $payment_option;
			}, array_keys( $enabled_payment_options_cfg ), array_values( $enabled_payment_options_cfg ) );

			$dao = new CompetitionDao();
			$dao->update( $competition );
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

		$competition_dao = new CompetitionDao();
		$competition     = $competition_dao->get( $_GET['tuja_competition'] );

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
			'tuja_competition_settings_fee_calculator',
			false
		);
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
					data-field-id="tuja_competition_settings_payment_options"
					data-root-name="tuja_competition_settings_payment_options"></div>',
			htmlentities( json_encode( $jsoneditor_config ) ),
			htmlentities( json_encode( $jsoneditor_values ) )
		);
	}
}
