<?php

namespace tuja\util\concurrency;

use PHPUnit\Framework\TestCase;

class LockValuesListTest extends TestCase {

	/**
	 * @test
	 */
	public function get_valid_ids_happy_path() {
		$list = ( new LockValuesList() )
			->add_value( 'a', 1 )
			->add_value( 'b', 1 )
			->add_value( 'c', 1 )
			->add_value( 'd', 2 );
		$ref  = ( new LockValuesList() )
			->add_value( 'b', 1 )
			->add_value( 'c', 2 )
			->add_value( 'd', 1 )
			->add_value( 'e', 1 );

		self::assertEquals( [
			'a', // a is valid because it's new (and thus cannot be too old already)
			'b' // b is valid because 1 == 1
			// c is NOT valid because 1 < 2 (reference is younger)
			// d is NOT valid because 2 > 1 (reference is old, which should not even be possible)
			// e is NOT valid because it's not even in the input list
		], $list->get_valid_ids( $ref ) );
	}

	/**
	 * @test
	 */
	public function get_invalid_ids_happy_path() {
		$list = ( new LockValuesList() )
			->add_value( 'a', 1 )
			->add_value( 'b', 1 )
			->add_value( 'c', 1 )
			->add_value( 'd', 2 );
		$ref  = ( new LockValuesList() )
			->add_value( 'b', 1 )
			->add_value( 'c', 2 )
			->add_value( 'd', 1 )
			->add_value( 'e', 1 );

		self::assertEquals( [
			// a is NOT included, because it's not in the reference list
			// b is NOT included, because 1 == 1
			'c', // c IS included, because 1 != 2
			'd', // d IS included, because 2 != 1
			// e is NOT included, because it's not even in the input list
		], $list->get_invalid_ids( $ref ) );
	}

	/**
	 * @test
	 */
	public function to_and_from_string_happy_path() {
		$ref = ( new LockValuesList() )
			->add_value( 'a', 1 )
			->add_value( 'b', 2 );

		$actual = LockValuesList::from_string( $ref->to_string() );

		self::assertEquals( [
			'a',
			'b'
		], $ref->get_valid_ids( $actual ) );
	}

	/**
	 * @test
	 * @dataProvider subset_different_key_data_types_data
	 */
	public function subset_different_key_data_types( array $input, LockValuesList $expected ) {
		$ref_locks = new LockValuesList( [
			100   => 'a',
			'101' => 'b',
			102   => 'c',
			'103' => 'd'
		] );

		$actual = $ref_locks->subset( $input );
		self::assertEquals( $expected, $actual );
	}

	public function subset_different_key_data_types_data(): array {
		return [
			[ [ 100 ], new LockValuesList( [ 100 => 'a' ] ) ],
			[ [ '100' ], new LockValuesList( [ 100 => 'a' ] ) ],
			[ [ 101 ], new LockValuesList( [ '101' => 'b' ] ) ],
			[ [ '101' ], new LockValuesList( [ 101 => 'b' ] ) ],
			[ [ '101', 102 ], new LockValuesList( [ '101' => 'b', 102 => 'c' ] ) ]
		];
	}
}
