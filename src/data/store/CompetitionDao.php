<?php

namespace tuja\data\store;

use ReflectionClass;
use tuja\data\model\Competition;
use tuja\util\Database;
use tuja\util\paymentoption\PaymentOption;

class CompetitionDao extends AbstractDao {
	function __construct() {
		parent::__construct();
		$this->table = Database::get_table( 'competition' );
	}

	function create( Competition $competition ) {
		$competition->validate();

		$affected_rows = $this->wpdb->insert( $this->table,
			array(
				'random_id'            => $this->id->random_string(),
				'name'                 => $competition->name,
//				'create_group_start'                  => self::to_db_date($competition->create_group_start),
//				'create_group_end'                    => self::to_db_date($competition->create_group_end),
//				'edit_group_start'                    => self::to_db_date($competition->edit_group_start),
//				'edit_group_end'                      => self::to_db_date($competition->edit_group_end),
				'event_start'          => self::to_db_date( $competition->event_start ),
				'event_end'            => self::to_db_date( $competition->event_end ),
				'initial_group_status' => $competition->initial_group_status,
				'app_config'           => json_encode( $competition->app_config ),
				'payment_instructions' => json_encode( self::to_payment_instructions( $competition->fee_calculator, $competition->payment_options ) )
			),
			array(
				'%s',
				'%s',
//				'%d',
//				'%d',
//				'%d',
//				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s'
			) );
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	function update( Competition $competition ) {
		$competition->validate();

		return $this->wpdb->update( $this->table,
			array(
				'name'                 => $competition->name,
//				'create_group_start'                  => self::to_db_date($competition->create_group_start),
//				'create_group_end'                    => self::to_db_date($competition->create_group_end),
//				'edit_group_start'                    => self::to_db_date($competition->edit_group_start),
//				'edit_group_end'                      => self::to_db_date($competition->edit_group_end),
				'event_start'          => self::to_db_date( $competition->event_start ),
				'event_end'            => self::to_db_date( $competition->event_end ),
				'initial_group_status' => $competition->initial_group_status,
				'app_config'           => json_encode( $competition->app_config ),
				'payment_instructions' => json_encode( self::to_payment_instructions( $competition->fee_calculator, $competition->payment_options ) )
			),
			array(
				'id' => $competition->id
			) );
	}

	function get( $id ): Competition {
		return $this->get_object(
			function ( $row ) {
				return self::to_competition( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE id = %d',
			$id );
	}

	function get_all() {
		return $this->get_objects(
			function ( $row ) {
				return self::to_competition( $row );
			},
			'SELECT * FROM ' . $this->table );
	}

	private static function to_competition( $result ): Competition {
		$c            = new Competition();
		$c->name      = $result->name;
		$c->id        = $result->id;
		$c->random_id = $result->random_id;
//		$c->create_group_start                     = self::from_db_date($result->create_group_start);
//		$c->create_group_end                       = self::from_db_date($result->create_group_end);
//		$c->edit_group_start                       = self::from_db_date($result->edit_group_start);
//		$c->edit_group_end                         = self::from_db_date($result->edit_group_end);
		$c->event_start = self::from_db_date( $result->event_start );
		$c->event_end   = self::from_db_date( $result->event_end );

		$default_app_config   = json_decode( file_get_contents( __DIR__ . '/CompetitionAppConfig.default.json' ), true );
		$stored_app_config    = json_decode( $result->app_config ?? '{}', true );
		$combined_app_config  = array_replace_recursive( $default_app_config, $stored_app_config );
		$c->app_config        = $combined_app_config;

		$payment_details = json_decode( $result->payment_instructions, true );

		if ( @$payment_details['fee_calculator']['class_name'] ) {
			try {
				$fee_calculator_class = new ReflectionClass( $payment_details['fee_calculator']['class_name'] );
				$c->fee_calculator    = $fee_calculator_class->newInstance();
				$c->fee_calculator->configure( $payment_details['fee_calculator']['config'] );
			} catch ( \Exception $e ) {
			}
		}

		$c->payment_options = array_map( function ( $cfg ) {
			$payment_option_class = new ReflectionClass( $cfg['class_name'] );
			$payment_option       = $payment_option_class->newInstance();
			$payment_option->configure( $cfg['config'] );

			return $payment_option;
		}, @$payment_details['payment_options'] ?: [] );

		$c->initial_group_status = $result->initial_group_status;

		return $c;
	}

	private static function to_payment_instructions( $fee_calculator, array $payment_options ) {
		return [
			'fee_calculator'  => $fee_calculator ? [
				'class_name' => ( new ReflectionClass( $fee_calculator ) )->getName(),
				'config'     => $fee_calculator->get_config()
			] : null,
			'payment_options' => array_map( function ( PaymentOption $payment_option ) {
				return [
					'class_name' => ( new ReflectionClass( $payment_option ) )->getName(),
					'config'     => $payment_option->get_config()
				];
			}, $payment_options )
		];
	}

	public function get_by_key( $competition_key ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_competition( $row );
			},
			'SELECT * FROM ' . $this->table . ' WHERE random_id = %s',
			$competition_key );
	}

	function delete( $id ) {
		$query_template = 'DELETE FROM ' . $this->table . ' WHERE id = %d';

		$affected_rows = $this->wpdb->query( $this->wpdb->prepare( $query_template, $id ) );

		$success = $affected_rows !== false && $affected_rows === 1;

		if ( ! $success ) {
			throw new Exception($this->wpdb->last_error);
}
	}
}
