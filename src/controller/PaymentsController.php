<?php
namespace tuja\controller;

use DateTime;
use Exception;
use tuja\controller\payments\MatchPaymentResult;
use tuja\controller\payments\MatchPaymentsResult;
use tuja\data\model\Competition;
use tuja\data\model\Group;
use tuja\data\model\payment\GroupPayment;
use tuja\data\model\payment\PaymentTransaction;
use tuja\data\model\Person;
use tuja\data\store\GroupDao;
use tuja\data\store\PaymentDao;
use tuja\data\store\PersonDao;
use tuja\util\Id;

class PaymentsController {
	private $payment_dao;
	private $groups_dao;
	private $people_dao;
	private $competition;

	const TRANSACTION_REPORT_MAGIC_STRING = '* Transaktionsrapport';

	public function __construct( Competition $competition ) {
		$this->payment_dao = new PaymentDao();
		$this->groups_dao  = new GroupDao();
		$this->people_dao  = new PersonDao();
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

				if ( strpos( $values[0], self::TRANSACTION_REPORT_MAGIC_STRING ) !== false ) {
					throw new Exception( 'Fel typ av rapport.' );
				}

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

	private static function find_international_phone_number( string $phone ) {
		return preg_filter( '/.*(\\+\\d+).*/', '$1', $phone );
	}

	private static function find_by_phone( array $people, string $phone_number ) {
		return array_filter(
			$people,
			function ( Person $person ) use ( $phone_number ) {
				return $person->phone === $phone_number;
			}
		);
	}

	private static function find_id( string $input ) {
		$regexp = '/.*([' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}).*/i';
		return preg_filter( $regexp, '$1', $input );
	}

	private static function find_by_group_key( array $groups, string $key ) {
		$key = strtolower( $key );
		return array_filter(
			$groups,
			function ( Group $group ) use ( $key ) {
				return strtolower( $group->random_id ) === $key;
			}
		);
	}

	private static function find_by_group_id( array $groups, int $id ) {
		return current(
			array_filter(
				$groups,
				function ( Group $group ) use ( $id ) {
					return $group->id === $id;
				}
			)
		);
	}

	public function match_transactions( array $transactions ): MatchPaymentsResult {
		$all_groups = $this->groups_dao->get_all_in_competition( $this->competition->id );
		$all_people = $this->people_dao->get_all_in_competition( $this->competition->id );
		return new MatchPaymentsResult(
			array_map(
				function ( PaymentTransaction $transaction ) use ( $all_groups, $all_people ) {
					$best_match        = null;
					$best_match_reason = '';

					$transaction_sender_phone_number = self::find_international_phone_number( $transaction->sender );
					if ( null !== $transaction_sender_phone_number ) {
						$matching_people = self::find_by_phone( $all_people, $transaction_sender_phone_number );
						if ( count( $matching_people ) === 1 ) {
							$matching_person   = current( $matching_people );
							$best_match        = self::find_by_group_id( $all_groups, $matching_person->group_id );
							$best_match_reason = "Lagets deltagare $matching_person->name har telefonnummer $matching_person->phone och transaktionen kommer från $transaction_sender_phone_number.";
						}
					}

					$transaction_message_group_key = self::find_id( $transaction->message );
					if ( null !== $transaction_message_group_key ) {
						$matches_by_group_key = array_unique(
							array_map(
								function ( Group $group ) {
									return $group->id;
								},
								self::find_by_group_key( $all_groups, $transaction_message_group_key )
							)
						);
						if ( count( $matches_by_group_key ) === 1 ) {
							$best_match        = self::find_by_group_id( $all_groups, current( $matches_by_group_key ) );
							$best_match_reason = "Laget har id $best_match->random_id och transaktionen nämner $transaction_message_group_key.";
						}
					}

					return new MatchPaymentResult( $transaction, $best_match, $best_match_reason );
				},
				$transactions
			)
		);
	}

	public function group_fee_status( Group $group, array $group_payments, DateTime $date ) {
		$fee_calculator  = $group->effective_fee_calculator;
		$amount_expected = $fee_calculator->calculate_fee( $group, $date );
		$description     = $fee_calculator->description();
		$amount_paid     = array_sum(
			array_map(
				function ( GroupPayment $payment ) {
					return $payment->amount / 100;
				},
				$group_payments
			)
		);
		$amount_diff     = $amount_expected - $amount_paid;
		$status_message  = '';
		if ( $amount_diff === 0 ) {
			$status_message = 'Okej.';
		} elseif ( $amount_diff < 0 ) {
			$status_message = 'Har betalat ' . number_format_i18n( -$amount_diff ) . ' kr för mycket.';
		} elseif ( $amount_diff > 0 ) {
			$status_message = number_format_i18n( $amount_diff ) . ' kr saknas.';
		}
		return array( $amount_expected, $amount_paid, $status_message, $description );
	}
}
