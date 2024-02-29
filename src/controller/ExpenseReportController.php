<?php
namespace tuja\controller;

use tuja\data\model\ExpenseReport;
use tuja\data\model\Competition;
use tuja\data\store\ExpenseReportDao;
use tuja\util\Id;
use Exception;

class ExpenseReportController {
	private $expense_report_dao = null;

	const ID_LENGTH = 4;

	function __construct(  ) {
		$this->expense_report_dao = new ExpenseReportDao();
	}

	public function create( ExpenseReport $expense_report ) {
		$result = $this->expense_report_dao->create( $expense_report );

		if ( false === $result ) {
			throw new Exception( 'Could not save expense report.' );
		}
	}

	public function exists( Competition $competition, string $id ) {
		return false !== $this->expense_report_dao->get( $competition, $id );
	}

	public static function get_new_id() {
		return (new Id())->random_unambiguous_letters(self::ID_LENGTH);
	}

	public static function is_id( string $id ) {
		return preg_match( '/^[' . Id::RANDOM_UNAMBIGUOUS_LETTERS . ']{' . self::ID_LENGTH . '}$/', $id );
	}
}
