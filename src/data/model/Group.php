<?php

namespace tuja\data\model;


use tuja\util\GroupCategoryCalculator;
use tuja\util\rules\RegistrationEvaluator;
use tuja\util\StateMachine;
use tuja\util\StateMachineException;

class Group {
	private static $group_calculators = [];

	const STATUS_CREATED = 'created';
	const STATUS_AWAITING_APPROVAL = 'awaiting_approval';
	const STATUS_ACCEPTED = 'accepted';
	const STATUS_INCOMPLETE_DATA = 'incomplete';
	const STATUS_AWAITING_CHECKIN = 'awaiting_checkin';
	const STATUS_CHECKEDIN = 'checkedin';
	const STATUS_CHECKEDOUT = 'checkedout';
	const STATUS_DELETED = 'deleted';

	const DEFAULT_STATUS = self::STATUS_CREATED;

	const STATUS_TRANSITIONS = [
		self::STATUS_CREATED           => [
			self::STATUS_ACCEPTED,
			self::STATUS_AWAITING_APPROVAL,
			self::STATUS_DELETED
		],
		self::STATUS_AWAITING_APPROVAL => [
			self::STATUS_ACCEPTED,
			self::STATUS_DELETED
		],
		self::STATUS_ACCEPTED          => [
			self::STATUS_CHECKEDIN,
			self::STATUS_AWAITING_CHECKIN,
			self::STATUS_INCOMPLETE_DATA,
			self::STATUS_DELETED
		],
		self::STATUS_INCOMPLETE_DATA   => [
			self::STATUS_ACCEPTED,
			self::STATUS_DELETED
		],
		self::STATUS_AWAITING_CHECKIN  => [
			self::STATUS_ACCEPTED,
			self::STATUS_CHECKEDIN,
			self::STATUS_DELETED
		],
		self::STATUS_CHECKEDIN         => [
			self::STATUS_CHECKEDOUT,
			self::STATUS_DELETED
		],
		self::STATUS_CHECKEDOUT        => [
			self::STATUS_DELETED
		],
		self::STATUS_DELETED           => [

		]
	];

	private $status;
	public $id;
	public $random_id;
	public $competition_id;
	public $name;
	public $category_id;
	public $age_competing_avg;
	public $age_competing_min;
	public $age_competing_max;
	public $count_competing;
	public $count_follower;
	public $count_team_contact;
	public $is_always_editable = false;
	public $note;

	public function __construct( $random_id = null ) {
		$this->status    = new StateMachine( null, self::STATUS_TRANSITIONS );
		$this->random_id = $random_id;
	}

	public function validate() {
		if ( strlen( trim( $this->name ) ) < 1 ) {
			throw new ValidationException( 'name', 'Namnet måste fyllas i.' );
		}
		if ( strlen( $this->name ) > 100 ) {
			throw new ValidationException( 'name', 'Namnet får inte vara längre än 100 bokstäver.' );
		}
		if ( $this->get_status() == null ) {
			throw new ValidationException( 'status', 'Status måste vara satt.' );
		}
		if ( strlen( $this->note ) > 65000 ) {
			throw new ValidationException( 'note', 'För långt meddelande.' );
		}
	}

	public function get_status() {
		return $this->status->get();
	}

	public function set_status( $new_status ) {
		try {
			$this->status->set( $new_status );
		} catch ( StateMachineException $e ) {
			throw new ValidationException( 'status', $e->getMessage() );
		}
	}

	public function evaluate_registration(): array {
		$category = $this->get_derived_group_category();
		if ( isset( $category ) ) {
			$rule_set  = $category->get_rule_set();
			$evaluator = new RegistrationEvaluator( $rule_set );

			return $evaluator->evaluate( $this );
		}

		return [];
	}

	public function get_derived_group_category() {
		return self::get_group_calculator( $this->competition_id )->get_category( $this );
	}

	private static function get_group_calculator( $competition_id ): GroupCategoryCalculator {
		if ( ! isset( self::$group_calculators[ $competition_id ] ) ) {
			self::$group_calculators[ $competition_id ] = new GroupCategoryCalculator( $competition_id );
		}

		return self::$group_calculators[ $competition_id ];
	}

	public function get_status_changes() {
		return $this->status->get_state_changes();
	}

	public function clear_status_changes() {
		$this->status->clear_status_changes();
	}

}