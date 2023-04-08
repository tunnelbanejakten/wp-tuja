<?php
namespace tuja\controller;

use DateTime;
use tuja\data\model\Competition;
use tuja\data\model\payment\PaymentTransaction;
use tuja\data\store\PaymentDao;

class PaymentsController {
	private $payment_dao;
	private $competition;

	public function __construct( Competition $competition ) {
		$this->payment_dao = new PaymentDao();
		$this->competition = $competition;
	}

	public function parse_swedbank_csv_swish_report( string $file_content ) : array {
		$lines = array_map(
			'trim',
			explode( "\n", $file_content )
		);
		return array_map(
			function ( string $line ) {
				$values = str_getcsv( $line );

				if ( count( $values ) !== 14 || ! is_numeric( $values[0] ) ) {
					return null;
				}

				list (
					$row_number,
					$clearing_number,
					$account_number,
					,
					$transaction_date_string,
					,
					$swish_number,
					$swish_name,
					$sender_number,
					$sender_name,
					$message,
					$transaction_time_string,
					$amount_string,
				) = $values;

				$transaction_date = DateTime::createFromFormat( 'Y-m-d G:i', "$transaction_date_string $transaction_time_string" );
				if ( false === $transaction_date ) {
					return null;
				}
				print_r( "$transaction_date_string $transaction_time_string" );
				$amount             = round( floatval( $amount_string ) * 100 );
				$sender_description = join( ', ', array( $sender_number, $sender_name ) );
				$key                = join(
					':',
					array(
						'swish',
						$transaction_date->format( 'c' ),
						$sender_number,
						$amount,
						md5( $message ),
					)
				);
				return new PaymentTransaction( 0, $this->competition->id, $key, $transaction_date, $message, $sender_description, $amount );
			},
			$lines
		);
	}
}
