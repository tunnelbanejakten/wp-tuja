<?php

namespace tuja\data\store;

use tuja\data\model\payment\PaymentTransaction;
use tuja\util\Database;

class PaymentDao extends AbstractDao {
	private $table_paymenttransaction;
	private $table_grouppayment;

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

	function get_all_in_competition( $competition_id ) {
		return $this->get_objects(
			function ( $row ) {
				return self::to_payment_transaction( $row );
			},
			'SELECT * FROM ' . $this->table_paymenttransaction . ' WHERE competition_id = %d',
			$competition_id
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
		);
	}

}
