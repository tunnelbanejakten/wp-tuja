<?php

namespace tuja\util;

use tuja\data\model\ValidationException;
use PHPUnit\Framework\TestCase;

class DateUtilsTest extends TestCase {

	/**
	 * @test
	 * @dataProvider fix_pno_valid_data
	 */
	public function fix_pno_valid($input, $expected) {
		$actual = DateUtils::fix_pno( $input );
		$this->assertEquals( $expected, $actual );
	}

	public function fix_pno_valid_data() {
		return [
			[ '8311090123', '19831109-0123' ],
			[ '831109-0123', '19831109-0123' ],
			[ '198311090123', '19831109-0123' ],
			[ '19831109-0123', '19831109-0123' ],
			[ '831109', '19831109-0000' ],
			[ '83-11-09', '19831109-0000' ],
			[ '63-11-09', '19631109-0000' ],
			[ '73-11-09', '19731109-0000' ],
			[ '03-11-09', '20031109-0000' ],
			[ '13-11-09', '20131109-0000' ],
			[ '19831109', '19831109-0000' ],
			[ '1983-11-09', '19831109-0000' ],
			[ '198311090000', '19831109-0000' ],
			[ '8311090000', '19831109-0000' ],
			[ '1983-11-09--0123', '19831109-0123' ]
		];
	}

	/**
	 * @test
	 * @dataProvider fix_pno_invalid_data
	 */
	public function fix_pno_invalid($input) {
		$this->expectException(ValidationException::class);
		DateUtils::fix_pno( $input );
	}

	public function fix_pno_invalid_data() {
		return [
			['19831109-012'],
			['19831109-01'],
			['12345'],
			['198300000000'],
			['8300000000'],
			['830000000000'],
			['1234567890'],
			['nej'],
			['19909999-0000']
		];
	}
}
