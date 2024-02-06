<?php

namespace tuja\frontend\router;


use tuja\data\model\Competition;
use tuja\frontend\FrontendView;
// use tuja\frontend\ExpenseReportEditor;
use tuja\util\Id;
use tuja\controller\ExpenseReportController;

class ExpenseReportInitiator implements ViewInitiator {
	const ACTION = 'utlagg';

	public static function link( Competition $competition, string $expense_report_key ) {
		return join( '/', [ get_site_url(), $competition->random_id, self::ACTION, strtolower($expense_report_key) ] );
	}

	function create_page( $path ): FrontendView {
		list ( $competition_key, $action, $expense_report_key ) = explode( '/', urldecode( $path ) );

		$url = 'https://docs.google.com/forms/d/e/1FAIpQLSeZvzLUMON30EzkEI9DJhJhCBPFCPo_uQAdDGzZO15STqRdZA/viewform?usp=pp_url&entry.29097398=' . strtoupper($expense_report_key);
		header('Location: '. $url);
		exit();
		// return new ExpenseReportEditor( $path, $competition_key, $expense_report_key );
	}

	function is_handler( $path ): bool {
		$parts = explode( '/', urldecode( $path ) );
		if ( count( $parts ) < 3 ) {
			return false;
		}
		list ( $competition_key, $action, $expense_report_key ) = $parts;

		return isset( $competition_key ) && isset( $expense_report_key ) && isset( $action )
		       && $action == self::ACTION
		       && preg_match( '/^[' . Id::RANDOM_CHARS . ']{' . Id::LENGTH . '}$/', $competition_key )
		       && ExpenseReportController::is_id( $expense_report_key ); 
	}
}