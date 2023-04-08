<?php

namespace tuja\admin;

use tuja\controller\PaymentsController;
use tuja\data\model\payment\PaymentTransaction;
use tuja\data\store\PaymentDao;

class PaymentsImport extends Payments {

	const ACTION_NAME_START = 'import_start';

	const VIEW_DEFAULT = 'views/payments-import.php';
	const VIEW_RESULT  = 'views/payments-import-result.php';

	public function __construct() {
		parent::__construct();
	}

	public function handle_post() {
		if ( ! isset( $_POST['tuja_action'] ) ) {
			return array( self::VIEW_DEFAULT, array() );
		}

		if ( $_POST['tuja_action'] == self::ACTION_NAME_START ) {
			$controller      = new PaymentsController( $this->competition );
			$transactions    = array_filter( $controller->parse_swedbank_csv_swish_report( $_POST['tuja_import_raw'] ) );
			$overall_success = array_reduce(
				$transactions,
				function ( bool $result, PaymentTransaction $transaction ) {
					$transaction->competition_id = $this->competition->id;
					$success                     = $this->payment_dao->upsert( $transaction );
					return $result && $success;
				},
				true
			);
			if ( $overall_success ) {
				AdminUtils::printSuccess( 'Importen gick bra' );
			} else {
				AdminUtils::printError( 'Något gick fel vid importen.' );
			}
			return array( self::VIEW_RESULT, $transactions );
		}
	}
	public function output() {
		list($view, $transactions) = $this->handle_post();

		include( $view );
	}
}
