<?php

namespace tuja\data\store;

use tuja\data\model\Group;
use tuja\data\model\payment\GroupPayment;
use tuja\data\model\payment\PaymentTransaction;
use tuja\util\Database;

class PaymentDao extends AbstractDao {
	private $table_paymenttransaction;
	private $table_grouppayment;

	const PAYMENTTRANSACTION_COLUMNS = array(
		'id',
		'competition_id',
		'id_key',
		'transaction_time',
		'message',
		'sender',
		'amount',
	);

	function __construct() {
		parent::__construct();
		$this->table_paymenttransaction = Database::get_table( 'paymenttransaction' );
		$this->table_grouppayment       = Database::get_table( 'team_payment' );
	}

	function upsert( PaymentTransaction $transaction ) {
		$transaction->validate();

		$query = '
		INSERT INTO ' . $this->table_paymenttransaction . '
			(id_key, competition_id, transaction_time, message, sender, amount)
			VALUES
			(%s, %d, %d, %s, %s, %d)
		ON DUPLICATE KEY UPDATE
			transaction_time = %d,
			message = %s,
			sender = %s,
			amount = %d
		';

		$affected_rows = $this->wpdb->query(
			$this->wpdb->prepare(
				$query,
				//
				$transaction->key,
				$transaction->competition_id,
				self::to_db_date( $transaction->transaction_time ),
				$transaction->message,
				$transaction->sender,
				$transaction->amount,
				//
				self::to_db_date( $transaction->transaction_time ),
				$transaction->message,
				$transaction->sender,
				$transaction->amount,
			)
		);

		$success = $affected_rows !== false;

		return $success;
	}

	function create_group_payment( int $group_id, int $amount, int $payment_transaction_id = 0, string $note = '' ) {
		$affected_rows = $this->wpdb->insert(
			$this->table_grouppayment,
			array(
				'team_id'               => $group_id,
				'amount'                => $amount,
				'note'                  => ! empty( $note ) ? $note : null,
				'paymenttransaction_id' => $payment_transaction_id > 0 ? $payment_transaction_id : null,
			),
			array(
				'%d',
				'%d',
				'%s',
				'%d',
			)
		);
		$success       = $affected_rows !== false && $affected_rows === 1;

		return $success ? $this->wpdb->insert_id : false;
	}

	function get_group_payments( int $competition_id ) {
		return array_reduce(
			$this->get_objects(
				function ( $row ) {
					return self::to_group_payent( $row );
				},
				'SELECT ' .
				' gp.*, ' .
				' pt.transaction_time, ' .
				' pt.message, ' .
				' pt.sender ' .
					' FROM ' . $this->table_grouppayment . ' AS gp ' .
				' INNER JOIN ' . Database::get_table( 'team' ) . ' AS t ' .
				' ON t.id = gp.team_id ' .
				' LEFT JOIN ' . $this->table_paymenttransaction . ' AS pt ' .
				' ON pt.id = gp.paymenttransaction_id ' .
				' WHERE t.competition_id = %d',
				$competition_id
			),
			function ( array $result, GroupPayment $group_payment ) {
				$result[ $group_payment->team_id ][] = $group_payment;
				return $result;
			},
			array()
		);
	}

	function get_group_payments_by_group( Group $group ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_group_payent( $row );
			},
			'SELECT ' .
			' gp.*, ' .
			' pt.transaction_time, ' .
			' pt.message, ' .
			' pt.sender ' .
			' FROM ' . $this->table_grouppayment . ' AS gp ' .
			' LEFT JOIN ' . $this->table_paymenttransaction . ' AS pt ' .
			' ON pt.id = gp.paymenttransaction_id ' .
			' WHERE gp.team_id = %d',
			$group->id
		);
	}

	function delete_group_payment( int $payment_id ) {
		$affected = $this->wpdb->delete(
			$this->table_grouppayment,
			array(
				'id' => $payment_id,
			),
			array(
				'%d',
			)
		);
		return $affected === 1;
	}

	function get_all_in_competition( int $competition_id ) {
		$main_columns = join(
			',',
			array_map(
				function ( string $column ) {
					return 'pt.' . $column;
				},
				self::PAYMENTTRANSACTION_COLUMNS
			)
		);
		return $this->get_objects(
			function ( $row ) {
				return self::to_payment_transaction( $row );
			},
			'SELECT ' .
			" $main_columns, SUM(gp.amount) AS groups_attribution_sum " .
			' FROM ' . $this->table_paymenttransaction . ' AS pt ' .
			' LEFT JOIN ' . $this->table_grouppayment . ' AS gp ' .
			' ON pt.id = gp.paymenttransaction_id ' .
			' WHERE competition_id = %d' .
			" GROUP BY $main_columns ",
			$competition_id
		);
	}

	private static function to_group_payent( $result ): GroupPayment {
		$paymenttransaction_description = null !== $result->paymenttransaction_id
		? sprintf(
			'%s, %s, %s',
			self::from_db_date( $result->transaction_time )->format( 'Y-m-d' ),
			$result->message,
			$result->sender
		)
			: '';
		return new GroupPayment(
			intval( $result->id ),
			intval( $result->team_id ),
			intval( $result->amount ),
			$result->note,
			null !== $result->paymenttransaction_id ? intval( $result->paymenttransaction_id ) : null,
			$paymenttransaction_description
		);
	}

	private static function to_payment_transaction( $result ): PaymentTransaction {
		return new PaymentTransaction(
			intval( $result->id ),
			intval( $result->competition_id ),
			$result->id_key,
			self::from_db_date( $result->transaction_time ),
			$result->message,
			$result->sender,
			intval( $result->amount ),
			intval( $result->groups_attribution_sum ),
		);
	}

}
