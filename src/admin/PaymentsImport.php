<?php

namespace tuja\admin;

use Exception;
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
			try {
				$controller = new PaymentsController( $this->competition );

				if ( isset( $_FILES['tuja_import_file'] ) && $_FILES['tuja_import_file']['size'] > 0 ) {
					$content         = file_get_contents( $_FILES['tuja_import_file']['tmp_name'] );
					$detected        = mb_detect_encoding( $content, 'ISO-8859-1,Windows-1252,UTF-8', true );
					$raw_import_data = mb_convert_encoding( $content, 'UTF-8', $detected );
				} else {
					$raw_import_data = $_POST['tuja_import_raw'];
				}

				$transactions = array_filter( $controller->parse_swedbank_csv_swish_report( $raw_import_data ) );
				if ( empty( $transactions ) ) {
					AdminUtils::printError( 'Inga transaktioner.' );
					return array( self::VIEW_DEFAULT, array() );
				}
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
			} catch ( Exception $e ) {
				AdminUtils::printException( $e );
				return array( self::VIEW_DEFAULT, array() );
			}
		}
	}
	public function output() {
		list($view, $transactions) = $this->handle_post();

		include( $view );
	}
}
