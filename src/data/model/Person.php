<?php

namespace tuja\data\model;

use tuja\util\StateMachine;
use tuja\util\StateMachineException;

class Person
{
	/*
	Valid values:
	- 8311090123
	- 831109-0123
	- 198311090123
	- 19831109-0123
	- 831109
	- 83-11-09
	- 19831109
	- 1983-11-09
	- 198311090000
	- 8311090000
	- 1983-11-09--0123

	Examples of invalid values:
	- 19831109-012
	- 19831109-01
	- 12345
	- 198300000000
	- 8300000000
	- 830000000000
	- 1234567890
	- nej
	*/
	const PNO_PATTERN = '^(19|20)?[0-9]{2}-?(0[1-9]|[1-2][0-9])-?[0-3][0-9](-*[0-9]{4})?$';
	const PHONE_PATTERN = '^\+?[0-9 -]{6,}$';

	const STATUS_CREATED = 'created';
	const STATUS_DELETED = 'deleted';

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
	private $status;

	public function __construct() {
		$this->status = new StateMachine( null, [
			self::STATUS_CREATED => [
				self::STATUS_DELETED
			],
			self::STATUS_DELETED => [
				self::STATUS_CREATED
			]
		] );
	}

	public static function is_valid_email_address( $email ) {
		return preg_match( '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i', $email ) === 1;
	}

	public static function is_valid_phone_number( $phone ) {
		return preg_match( '/' . self::PHONE_PATTERN . '/', $phone ) === 1;
	}

	public function validate() {
		if ( empty( trim( $this->name ) ) ) {
			throw new ValidationException( 'name', 'Namnet måste fyllas i.' );
		}
		if ( strlen( $this->name ) > 100 ) {
			throw new ValidationException( 'name', 'Namnet får inte vara längre än 100 bokstäver.' );
		}
		if ( strlen( $this->email ) > 100 ) {
			throw new ValidationException( 'email', 'E-postadress får bara vara 100 tecken lång.' );
		}
		if ( ! empty( trim( $this->email ) ) && ! self::is_valid_email_address( $this->email ) ) {
			throw new ValidationException( 'email', 'E-postadressen ser konstig ut.' );
		}
		if ( strlen( $this->phone ) > 100 ) {
			throw new ValidationException( 'phone', 'Telefonnumret får bara vara 100 tecken långt.' );
		}
		if ( ! empty( trim( $this->phone ) ) && ! self::is_valid_phone_number( $this->phone ) ) {
			throw new ValidationException( 'phone', 'Telefonnummer ser konstigt ut.' );
		}
		if ( strlen( $this->food ) > 65000 ) {
			throw new ValidationException( 'food', 'För lång text om mat och allergier.' );
		}
		// TODO: Require birthday when adding new group members?
//        if (empty(trim($this->pno))) {
//            throw new ValidationException('pno', 'Födelsedag och sånt måste fyllas i');
//        }
		if ( ! empty( trim( $this->pno ) ) && preg_match( '/' . self::PNO_PATTERN . '/', $this->pno ) !== 1 ) {
			throw new ValidationException( 'pno', 'Födelsedag och sånt ser konstigt ut' );
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
		$person->set_as_extra_contact();

		return $person;
	}

	public function set_role_flags($is_competing, $is_attending, $is_group_contact) {
		$this->is_competing                  = $is_competing;
		$this->is_attending = $is_attending;
		$this->is_group_contact              = $is_group_contact;
	}

	public function set_as_adult_supervisor() {
		$this->is_competing                  = false;
		$this->is_attending = true;
		$this->is_group_contact              = true;
	}

	public function set_as_regular_group_member() {
		$this->is_competing                  = true;
		$this->is_attending = true;
		$this->is_group_contact              = false;
	}

	public function set_as_group_leader() {
		$this->is_competing                  = true;
		$this->is_attending = true;
		$this->is_group_contact              = true;
	}

	public function set_as_extra_contact() {
		$this->is_competing                  = false;
		$this->is_attending = false;
		$this->is_group_contact              = true;
	}

	public function is_attending(): bool {
		return $this->is_attending;
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
}