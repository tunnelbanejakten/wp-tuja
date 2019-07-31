<?php

namespace tuja\admin;


use tuja\data\store\CompetitionDao;

abstract class AbstractReport {
	protected $competition;

	public function __construct() {
		$this->competition = ( new CompetitionDao() )->get( $_GET['tuja_competition'] );
	}

	abstract function get_rows(): array;

	abstract function output_html( array $rows ): array;

	public function output() {
		if ( ! $this->competition ) {
			print 'Could not find competition';

			return;
		}

		$rows = $this->get_rows();

		switch ( $_GET['tuja_report_format'] ) {
			case 'csv':
				$this->output_csv( $rows );
				break;
			default:
				$this->output_html( $rows );
				include( 'views/reports-footer.php' );
				break;
		}
	}

	protected function output_csv( array $rows ) {
		header( 'Content-Type: text/csv; charset=ISO-8859-1' );
		header( sprintf( 'Content-Disposition: attachment; filename="tuja-%s-%d.csv"',
			strtolower( substr( get_class( $this ), strrpos( get_class( $this ), '\\' ) + 1 ) ),
			time() ) );
		$handle = tmpfile();
		if ( $handle !== false ) {
			foreach ( $rows as $row ) {
				fputcsv( $handle, $row );
			}
			rewind( $handle );
			fpassthru( $handle );
			fclose( $handle );
		}
	}
}