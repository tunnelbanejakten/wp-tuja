<?php

namespace tuja\admin\reportgenerators;


use tuja\admin\AbstractCompetitionPage;

abstract class AbstractReport extends AbstractCompetitionPage {
	abstract function get_rows(): array;

	abstract function output_html( array $rows );

	public function output() {
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