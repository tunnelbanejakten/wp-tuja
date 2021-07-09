<?php

namespace tuja\data\model;


use Exception;
use tuja\data\store\GroupCategoryDao;
use tuja\util\Id;
use tuja\util\Random;
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
	private $category_obj = null;
	public $age_competing_avg;
	public $age_competing_min;
	public $age_competing_max;
	public $count_competing;
	public $count_follower;
	public $count_team_contact;
	public $map_id;
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
		if ( strlen( $this->city ) > 30 ) {
			throw new ValidationException( 'name', 'Ortsnamnet får inte vara längre än 30 bokstäver.' );
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
		try {
			$category = $this->get_category();
			if ( isset( $category ) ) {
				$evaluator = new RegistrationEvaluator( $category->get_rules() );

				return $evaluator->evaluate( $this );
			}

			return [];
		} catch ( Exception $e ) {
			return [];
		}
	}

	public function get_category(): GroupCategory {
		if ( $this->category_obj == null ) {

			$group_category_dao = new GroupCategoryDao();
			$obj                = $group_category_dao->get( $this->category_id );

			if ( $obj == false ) {
				throw new Exception( 'Cannot find category' );
			}
			$this->category_obj = $obj;
		}

		return $this->category_obj;
	}

	public function get_status_changes() {
		return $this->status->get_state_changes();
	}

	public function clear_status_changes() {
		$this->status->clear_status_changes();
	}

	public function is_edit_allowed(): bool {
		if ( $this->is_always_editable ) {
			return true;
		}

		try {
			return $this->get_category()->get_rules()->is_update_registration_allowed();
		} catch ( Exception $e ) {
			return false;
		}
	}


	public static function sample(): Group {
		$group = new Group();

		$group->random_id = ( new Id() )->random_string();
		$group->note      = 'Vi gillar ' . Random::string( [
				'godis',
				'glass',
				'scouting',
				'skattjakter',
				'spårningar',
				'läger',
				'hajk',
				'tunnelbanejakter',
				'eld',
				'lägerbål',
				'tunnelbanan',
				'fika'
			] );
		$group->name      = Random::string( [
			'Griffonen',
			'Kentauren',
			'Enhörningen',
			'Faunen',
			'Gripen',
			'Sjömannen',
			'Ekorren',
			'Älgen',
			'Vargen',
			'Bävern',
			'Björnen',
			'Järven',
			'Uttern',
			'Räven',
			'Vesslan',
			'Draken',
			'Stora',
			'Ödlan',
			'Kräftan',
			'Pegasus',
			'Tjuren',
			'Ugglan',
			'Örnen',
			'Tjädern',
			'Tranan',
			'Korpen',
			'Talgoxen'
		] );

		return $group;
	}

}