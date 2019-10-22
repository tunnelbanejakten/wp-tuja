<?php

namespace tuja\data\model;


use tuja\util\GroupCategoryCalculator;
use tuja\util\rules\RegistrationEvaluator;

class Group
{
	private static $group_calculators = [];

	private $status_changes = [];

	const STATUS_CREATED = 'created';
	const STATUS_AWAITING_APPROVAL = 'awaiting_approval';
	const STATUS_ACCEPTED = 'accepted';
	const STATUS_INCOMPLETE_DATA = 'incomplete';
	const STATUS_AWAITING_CHECKIN = 'awaiting_checkin';
	const STATUS_CHECKEDIN = 'checkedin';
	const STATUS_CHECKEDOUT = 'checkedout';
	const STATUS_DELETED = 'deleted';

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

	private $status = null;
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
	public $is_always_editable;

	public function validate() {
		if ( strlen(trim($this->name)) < 1) {
			throw new ValidationException('name', 'Namnet måste fyllas i.');
		}
		if ( strlen($this->name) > 100) {
			throw new ValidationException('name', 'Namnet får inte vara längre än 100 bokstäver.');
		}
		if ( ! in_array( $this->get_status(), array_keys( self::STATUS_TRANSITIONS ) ) ) {
			throw new ValidationException( 'name', 'Ogiltig status.' );
		}
	}

	public function get_status() {
		return $this->status;
	}

	public function set_status( $new_status ) {
		$old_status = $this->status;
		if ( $old_status == $new_status ) {
			return;
		}
		if ( ! in_array( $new_status, array_keys( self::STATUS_TRANSITIONS ) ) ) {
			throw new ValidationException( 'status', 'Status ' . $new_status . ' is not defined.' );
		}
		if ( $this->get_status() != null ) {
			if ( ! isset( self::STATUS_TRANSITIONS[ $old_status ] ) ) {
				throw new ValidationException( 'status', 'No state transitions defined for ' . $old_status . '. Is this an old status? Is data migration needed?' );
			}
			if ( ! in_array( $new_status, self::STATUS_TRANSITIONS[ $this->get_status() ] ) ) {
				throw new ValidationException( 'status', 'Transition from ' . $this->get_status() . ' to ' . $new_status . ' not permitted. Permitted transitions: ' . join( ', ', self::STATUS_TRANSITIONS[ $this->get_status() ] ) . '.' );
			}
		}

		$this->status = $new_status;

		$this->status_changes[] = [ $old_status, $new_status ];
	}

	public function evaluate_registration() {
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
		return $this->status_changes;
	}

}