<?php

namespace tuja\admin\reportgenerators;

abstract class AbstractListReport extends AbstractReport {
	protected function output_csv( array $rows ) {
		$header_rows = count( $rows ) > 0 ? array( array_keys( $rows[0] ) ) : array();
		parent::output_csv(
			array_merge(
				$header_rows,
				$rows
			)
		);
	}
}
