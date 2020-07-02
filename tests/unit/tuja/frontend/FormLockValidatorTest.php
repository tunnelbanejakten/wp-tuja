<?php

namespace tuja\frontend;

use Exception;
use DateInterval;
use DateTime;
use PHPUnit\Framework\TestCase;
use tuja\data\model\Group;
use tuja\data\model\Response;
use tuja\data\store\ResponseDao;
use tuja\util\concurrency\LockValuesList;

class FormLockValidatorTest extends TestCase {

	/**
	 * @test
	 * @dataProvider check_optimistic_lock_data
	 */
	public function check_optimistic_lock( array $users_locks, array $current_locks, bool $is_happy_case ) {
		// arrange
		$group     = Group::sample();
		$group->id = 1;

		$base_date         = new DateTime();
		$response_dao_mock = $this->getMockBuilder( ResponseDao::class )
		                          ->disableOriginalConstructor()
		                          ->getMock();
		$response_dao_mock->method( 'get_latest_by_group' )
		                  ->willReturn( array_map( function ( $question_id, $duration ) use ( $group, $base_date ) {
			                  return $this->mock_response( $group->id, $question_id, $this->mock_past_date( $base_date, $duration ) );
		                  }, array_keys( $current_locks ), array_values( $current_locks ) ) );

		$validator = new FormLockValidator( $response_dao_mock, $group );

		// act

		if ( ! $is_happy_case ) {
			$this->expectException( Exception::class );
		}

		$input = new LockValuesList(
			array_combine(
				array_keys( $users_locks ),
				array_map( function ( string $duration ) use ( $base_date ) {
					return $this->mock_past_date( $base_date, $duration )->getTimestamp();
				}, array_values( $users_locks ) )
			)
		);
		$validator->check_optimistic_lock( $input );

		// assert

		self::assertTrue( $is_happy_case );
	}

	public function check_optimistic_lock_data(): array {
		return [
			[
				[
					'101' => 'PT2M' // User is looking at 2 minutes old answer...
				],
				[
					'100' => 'PT20M', // Should be ignored
					'101' => 'PT2M', // ...which is the latest timestamp.
				],
				true // Timestamps match for question 101
			],
			[
				[
					'101' => 'PT20M' // User is looking at 20 minutes old answer...
				],
				[
					'100' => 'PT20M', // Should be ignored
					'101' => 'PT2M', // ...but another answer was added only 2 minutes ago.
				],
				false // Timestamps DO NOT match for question 101
			],
			[
				[
					'101' => 'PT1M' // User is trying to fool the system by faking a too recent answer (1 minute old)
				],
				[
					'101' => 'PT2M' // ...but the most recent answer is actually 2 minutes old.
				],
				false // Timestamps DO NOT match for question 101
			]
		];
	}

	/**
	 * @test
	 */
	public function get_optimistic_lock_value() {
		// arrange
		$group     = Group::sample();
		$group->id = 1;

		$base_date         = new DateTime();
		$response_dao_mock = $this->getMockBuilder( ResponseDao::class )
		                          ->disableOriginalConstructor()
		                          ->getMock();

		$response_dao_mock->method( 'get_latest_by_group' )
		                  ->willReturn( [
			                  $this->mock_response( $group->id, '100', $this->mock_past_date( $base_date, 'PT1M' ) ),
			                  $this->mock_response( $group->id, '101', $this->mock_past_date( $base_date, 'PT2M' ) ),
			                  $this->mock_response( $group->id, '102', $this->mock_past_date( $base_date, 'PT3M' ) ),
		                  ] );

		$validator = new FormLockValidator( $response_dao_mock, $group );

		// act
		$actual = $validator->get_optimistic_lock_value( [ '101', '102', '103' ] );

		// assert
		$expected = new LockValuesList( [
			101 => $this->mock_past_date( $base_date, 'PT2M' )->getTimestamp(),
			102 => $this->mock_past_date( $base_date, 'PT3M' )->getTimestamp(),
			103 => 0 // Default to 0 for questions which the team has not yet answered
		] );

		self::assertEquals( $expected, $actual );
	}

	private function mock_past_date( DateTime $base_date, $duration ): DateTime {
		return ( clone $base_date )->sub( new DateInterval( $duration ) );
	}

	private function mock_response( int $group_id, string $question_id, DateTime $created_at ): Response {
		$response                   = new Response();
		$response->id               = uniqid();
		$response->group_id         = $group_id;
		$response->created          = $created_at;
		$response->form_question_id = $question_id;

		return $response;
	}
}
