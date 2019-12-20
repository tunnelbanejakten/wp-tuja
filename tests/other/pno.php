<?php

include_once '../src/data/model/ValidationException.php';
include_once '../src/data/model/Person.php';
include_once '../src/util/DateUtils.php';

use tuja\data\model\ValidationException;
use tuja\util\DateUtils;

const VALID = [
	'8311090123'       => '19831109-0123',
	'831109-0123'      => '19831109-0123',
	'198311090123'     => '19831109-0123',
	'19831109-0123'    => '19831109-0123',
	'831109'           => '19831109-0000',
	'83-11-09'         => '19831109-0000',
	'63-11-09'         => '19631109-0000',
	'73-11-09'         => '19731109-0000',
	'03-11-09'         => '20031109-0000',
	'13-11-09'         => '20131109-0000',
	'19831109'         => '19831109-0000',
	'1983-11-09'       => '19831109-0000',
	'198311090000'     => '19831109-0000',
	'8311090000'       => '19831109-0000',
	'1983-11-09--0123' => '19831109-0123'
];

const INVALID = [
	'19831109-012',
	'19831109-01',
	'12345',
	'198300000000',
	'8300000000',
	'830000000000',
	'1234567890',
	'nej',
	'19909999-0000'
];

foreach ( VALID as $input => $expected ) {
	$actual = DateUtils::fix_pno( $input );
	assert( $actual == $expected, $input . ' was not converted as expected.' );
}

foreach ( INVALID as $input ) {
	try {
		$actual = DateUtils::fix_pno( $input );
		assert( false, $input . ' should have failed but instead ' . $actual . ' was returned.' );
	} catch ( ValidationException $e ) {
	}
}
