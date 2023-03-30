<?php

namespace tuja\data\model;

use Exception;
use tuja\util\DateUtils;
use tuja\util\Id;
use tuja\util\Random;
use tuja\util\rules\GroupCategoryRules;
use tuja\util\StateMachine;
use tuja\util\StateMachineException;

class Person {
	const STATUS_CREATED   = 'created';
	const STATUS_CHECKEDIN = 'checkedin';
	const STATUS_DELETED   = 'deleted';

	const DEFAULT_STATUS = self::STATUS_CREATED;

	public $id;
	public $random_id;
	public $name;
	public $group_id;
	public $phone;
	public $phone_verified;
	public $email;
	public $email_verified;
	public $food;
	private $is_competing;
	private $is_group_contact;
	private $is_attending;
	public $pno;
	public $age;
	public $note;
	private $status;

	// Changing these will affect GroupCategoryRules as well
	const PERSON_TYPE_LEADER     = 'leader';
	const PERSON_TYPE_REGULAR    = 'regular';
	const PERSON_TYPE_SUPERVISOR = 'supervisor';
	const PERSON_TYPE_ADMIN      = 'admin';
	const PERSON_TYPES           = array(
		// Order affects presentation in GroupPeopleEditor
		self::PERSON_TYPE_LEADER,
		self::PERSON_TYPE_REGULAR,
		self::PERSON_TYPE_SUPERVISOR,
		self::PERSON_TYPE_ADMIN,
	);
	const PERSON_TYPE_LABELS     = array(
		self::PERSON_TYPE_LEADER     => 'Lagledare',
		self::PERSON_TYPE_REGULAR    => 'Deltagare',
		self::PERSON_TYPE_SUPERVISOR => 'Medföljande vuxen',
		self::PERSON_TYPE_ADMIN      => 'Administratör',
	);
	const STATUS_TRANSITIONS     = array(
		self::STATUS_CREATED   => array(
			self::STATUS_CHECKEDIN,
			self::STATUS_DELETED,
		),
		self::STATUS_CHECKEDIN => array(
			self::STATUS_CREATED,
			self::STATUS_DELETED,
		),
		self::STATUS_DELETED   => array(
			self::STATUS_CREATED,
		),
	);

	public function __construct() {
		$this->status = new StateMachine(
			null,
			self::STATUS_TRANSITIONS
		);
	}

	public static function is_valid_email_address( $email ) {
		return preg_match( '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i', $email ) === 1;
	}

	public static function is_valid_phone_number( $phone ) {
		return preg_match( '/' . GroupCategoryRules::PHONE_PATTERN . '/', $phone ) === 1;
	}

	public function validate( GroupCategoryRules $rules ) {
		if ( $rules == null ) {
			throw new Exception( 'Group category rules not specified' );
		}

		$rules->validate_person( $this );

		if ( strlen( $this->email ) > 100 ) {
			throw new ValidationException( 'email', 'E-postadress får bara vara 100 tecken lång.' );
		}
		if ( strlen( $this->phone ) > 100 ) {
			throw new ValidationException( 'phone', 'Telefonnumret får bara vara 100 tecken långt.' );
		}
		if ( strlen( $this->name ) > 100 ) {
			throw new ValidationException( 'name', 'Namnet får inte vara längre än 100 bokstäver.' );
		}
		if ( strlen( $this->food ) > 65000 ) {
			throw new ValidationException( 'food', 'För lång text om mat och allergier.' );
		}
		if ( strlen( $this->note ) > 65000 ) {
			throw new ValidationException( 'note', 'För långt meddelande.' );
		}
		if ( ! empty( trim( $this->pno ) ) ) {
			try {
				DateUtils::fix_pno( $this->pno );
			} catch ( ValidationException $e ) {
				throw new ValidationException( 'pno', $e->getMessage() );
			}
		}
		if ( $this->get_status() == null ) {
			throw new ValidationException( 'status', 'Status måste vara satt.' );
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

	public static function from_email( string $email ) {
		$person        = new Person();
		$person->name  = substr( $email, 0, strpos( $email, '@' ) );
		$person->email = $email;
		$person->set_type( Person::PERSON_TYPE_ADMIN );

		return $person;
	}

	public function set_role_flags( $is_competing, $is_attending, $is_group_contact ) {
		$this->is_competing     = $is_competing;
		$this->is_attending     = $is_attending;
		$this->is_group_contact = $is_group_contact;
	}

	public function set_type( string $type ) {
		switch ( $type ) {
			case self::PERSON_TYPE_LEADER:
				$this->is_competing     = true;
				$this->is_attending     = true;
				$this->is_group_contact = true;
				break;
			case self::PERSON_TYPE_REGULAR:
				$this->is_competing     = true;
				$this->is_attending     = true;
				$this->is_group_contact = false;
				break;
			case self::PERSON_TYPE_SUPERVISOR:
				$this->is_competing     = false;
				$this->is_attending     = true;
				$this->is_group_contact = true;
				break;
			case self::PERSON_TYPE_ADMIN:
				$this->is_competing     = false;
				$this->is_attending     = false;
				$this->is_group_contact = true;
				break;
			default:
				throw new Exception( 'Unsupported type of person: ' . $type );
		}
	}

	public function get_type(): string {
		$helper = function ( bool $is_competing, bool $is_attending, bool $is_group_contact ) {
			return $this->is_competing == $is_competing &&
				   $this->is_attending == $is_attending &&
				   $this->is_group_contact == $is_group_contact;
		};

		if ( $helper( true, true, true ) ) {
			return self::PERSON_TYPE_LEADER;
		} elseif ( $helper( true, true, false ) ) {
			return self::PERSON_TYPE_REGULAR;
		} elseif ( $helper( false, true, true ) ) {
			return self::PERSON_TYPE_SUPERVISOR;
		} elseif ( $helper( false, false, true ) ) {
			return self::PERSON_TYPE_ADMIN;
		} else {
			throw new Exception( 'Person cannot be mapped to one of the predefined types.' );
		}
	}

	public function get_short_description() {
		return $this->name ?: $this->get_type_label();
	}

	public function get_type_label(): string {
		return self::PERSON_TYPE_LABELS[ $this->get_type() ] ?? 'okänd';
	}

	public function is_attending(): bool {
		return $this->is_attending;
	}

	public function is_adult(): bool {
		return $this->is_adult_supervisor() || ( isset( $this->age ) && $this->age >= 18 );
	}

	public function is_adult_supervisor(): bool {
		return ! $this->is_competing && $this->is_attending;
	}

	public function is_competing(): bool {
		return $this->is_competing;
	}

	public function is_regular_group_member(): bool {
		return $this->is_competing && $this->is_attending && ! $this->is_group_contact;
	}

	public function is_contact(): bool {
		return $this->is_group_contact;
	}

	public function is_group_leader(): bool {
		return $this->is_competing && $this->is_attending && $this->is_group_contact;
	}

	public function get_formatted_age(): string {
		if ( 0 == $this->age ) {
			return '-';
		}
		$total_months = round( $this->age * 12 );
		$months       = $total_months % 12;
		$years        = ( $total_months - $months ) / 12;
		return $months > 0 ? "$years år $months mån" : "$years år";
	}

	public static function sample(): Person {
		$person = new Person();

		$person->name      = Random::string(
			array(
				'Alice',
				'Lucas',
				'Olivia',
				'Liam',
				'Astrid',
				'William',
				'Maja',
				'Elias',
				'Vera',
				'Noah',
				'Ebba',
				'Hugo',
				'Ella',
				'Oliver',
				'Wilma',
				'Oscar',
				'Alma',
				'Adam',
				'Lilly',
				'Matteo',
			)
		);
		$person->random_id = ( new Id() )->random_string();
		$person->note      = 'Scouting är ' . Random::string( array( 'kul', 'underbart', 'magiskt', 'fantastiskt' ) );
		$person->phone     = '070-123 45 67';
		$person->food      = Random::string( array( 'gluten', 'laktos' ) ) . 'intolerant';
		$person->email     = strtolower( $person->name ) . '@example.com';
		$person->pno       = rand( 2000, 2007 ) . rand( 10, 12 ) . rand( 10, 30 ) . '-0000';

		return $person;
	}
}
