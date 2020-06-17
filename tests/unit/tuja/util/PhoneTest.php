<?php

namespace tuja\util;

use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase {

	public function fix_phone_number_data() {
		return [
			[
				'070 123 456',
				'+4670123456'
			],
			[
				'070-123 456 ',
				'+4670123456'
			],
			[
				'00467/0123 456 ',
				'+4670123456'
			],
			[
				'071-2345678',
				'+46712345678'
			]
		];
	}

	/**
	 * @test
	 * @dataProvider fix_phone_number_data
	 */
	public function fix_phone_number( $input, $expected ) {
		$actual = Phone::fix_phone_number( $input );
		$this->assertEquals( $expected, $actual, 'Normilizing phone number ' . $input );
	}
}
