<?php

namespace tuja\data\store;

use DateTime;
use DateTimeInterface;
use Exception;
use tuja\data\model\Group;
use tuja\util\Anonymizer;
use tuja\data\model\Person;
use tuja\util\DateUtils;
use tuja\util\Database;
use tuja\util\Id;
use tuja\util\Phone;

class PersonDao extends AbstractDao {
	const QUERY_COLUMNS = 'p.*, pp.*, (DATEDIFF(CURDATE(), STR_TO_DATE(LEFT(pp.pno, 8), \'%%Y%%m%%d\')) / 365.25) age';

	private $props_table;

	function __construct() {
		parent::__construct();
		$this->table       = Database::get_table( 'person' );
		$this->props_table = Database::get_table( 'person_properties' );
	}

	function create( Person $person ) {
		$person->set_status( Person::DEFAULT_STATUS );

		$person->validate( $this->get_group( $person )->get_category()->get_rules() );

		$affected_rows = $this->wpdb->insert( $this->table,
			array(
				'random_id' => $this->id->random_string()
			),
			array(
				'%s',
			) );

		$success = $affected_rows !== false && $affected_rows === 1;

		if ( ! $success ) {
			return false;
		}

		$person->id = $this->wpdb->insert_id;

		$success = $this->add_record( $person, $person->get_status() );

		return $success ? $person->id : false;
	}

	function update( Person $person ) {
		$person->validate( $this->get_group( $person )->get_category()->get_rules() );

		$success = $this->add_record( $person, $person->get_status() );

		return $success ? $person->id : false;
	}

	private function add_record( Person $person, $status = Person::STATUS_CREATED ) {
		$affected_rows = $this->wpdb->insert( $this->props_table,
			array(
				'person_id'  => $person->id,
				'created_at' => self::to_db_date( new DateTime() ),
				'status'     => $status,

				'name'            => $person->name ?? '', // Database has not-null constraint.
				'team_id'         => $person->group_id,
				'phone'           => $person->phone,
				'email'           => $person->email,
				'food'            => $person->food,
				'is_competing'    => $person->is_competing() ? 1 : 0,
				'is_team_contact' => $person->is_contact() ? 1 : 0,
				'is_attending'    => $person->is_attending() ? 1 : 0,
				'pno'             => DateUtils::fix_pno( $person->pno ),
				'note'            => $person->note
			),
			array(
				'%d',
				'%d',
				'%s',

				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s'
			) );

		return $affected_rows !== false && $affected_rows === 1;
	}

	function get( $id, DateTimeInterface $date = null ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_person( $row );
			},
			'
			SELECT 
				' . self::QUERY_COLUMNS . ' 
			FROM 
				' . $this->table . ' AS p 
				INNER JOIN 
				' . $this->props_table . ' AS pp 
				ON p.id = pp.person_id 
			WHERE 
				pp.id IN (
					SELECT MAX(id) 
					FROM ' . $this->props_table . ' 
					WHERE person_id = %d AND created_at <= %d
				)',
			$id,
			self::to_db_date( $date ?: new DateTime() ) );
	}

	function get_by_key( $key, $date = null ) {
		return $this->get_object(
			function ( $row ) {
				return self::to_person( $row );
			},
			'
			SELECT 
				' . self::QUERY_COLUMNS . ' 
			FROM 
				' . $this->table . ' AS p 
				INNER JOIN 
				' . $this->props_table . ' AS pp 
				ON p.id = pp.person_id 
			WHERE 
				p.random_id = %s
				AND pp.id IN (
					SELECT MAX(id) 
					FROM ' . $this->props_table . ' 
					WHERE created_at <= %d
					GROUP BY person_id
				)',
			$key,
			self::to_db_date( $date ?: new DateTime() ) );
	}

	public function get_by_contact_data( $competition_id, $from, $date = null ) {
		$phone   = Phone::fix_phone_number( $from );
		$matches = array_filter(
		// TODO: The result of get_all_in_competition can maybe be cached to improve efficiency.
			$this->get_all_in_competition( $competition_id ),
			function ( Person $person ) use ( $phone ) {
				return Phone::fix_phone_number( $person->phone ) == $phone;
			} );
		if ( count( $matches ) == 1 ) {
			return current( $matches );
		}

		return null;
	}

	function get_all_in_group( $group_id, $include_deleted = false, $date = null ) {
		$objects = $this->get_objects(
			function ( $row ) {
				return self::to_person( $row );
			},
			'
			SELECT 
				' . self::QUERY_COLUMNS . ' 
			FROM 
				' . $this->table . ' AS p 
				INNER JOIN 
				' . $this->props_table . ' AS pp 
				ON p.id = pp.person_id 
			WHERE 
				pp.team_id = %d
				AND pp.id IN (
					SELECT MAX(id) 
					FROM ' . $this->props_table . ' 
					WHERE created_at <= %d
					GROUP BY person_id
				)
			ORDER BY
				pp.name',
			$group_id,
			self::to_db_date( $date ?: new DateTime() ) );

		return $include_deleted ? $objects : array_filter( $objects, function ( Person $person ) {
			return $person->get_status() != Person::STATUS_DELETED;
		} );
	}

	function get_all_in_competition( $competition_id, $include_deleted = false, $date = null ) {
		$objects = $this->get_objects(
			function ( $row ) {
				return self::to_person( $row );
			},
			'
			SELECT 
				' . self::QUERY_COLUMNS . ' 
			FROM 
				' . $this->table . ' AS p 
				INNER JOIN 
				' . $this->props_table . ' AS pp 
				ON p.id = pp.person_id
				INNER JOIN 
				' . Database::get_table( 'team' ) . ' AS t 
				ON pp.team_id = t.id  
			WHERE 
				t.competition_id = %d
				AND pp.id IN (
					SELECT MAX(id) 
					FROM ' . $this->props_table . ' 
					WHERE created_at <= %d
					GROUP BY person_id
				)
			ORDER BY
				pp.name',
			$competition_id,
			self::to_db_date( $date ?: new DateTime() ) );

		return $include_deleted ? $objects : array_filter( $objects, function ( Person $person ) {
			return $person->get_status() != Person::STATUS_DELETED;
		} );
	}

	function search( $competition_id, string $query, $date = null ) {
		$objects = $this->get_objects(
			function ( $row ) {
				return self::to_person( $row );
			},
			'
			SELECT 
				' . self::QUERY_COLUMNS . ' 
			FROM 
				' . $this->table . ' AS p 
				INNER JOIN 
				' . $this->props_table . ' AS pp 
				ON p.id = pp.person_id
				INNER JOIN 
				' . Database::get_table( 'team' ) . ' AS t 
				ON pp.team_id = t.id  
			WHERE 
				t.competition_id = %d
				AND (
					pp.name LIKE %s OR
					pp.phone LIKE %s OR
					pp.email LIKE %s OR
					pp.pno LIKE %s
				)
				AND pp.id IN (
					SELECT MAX(id) 
					FROM ' . $this->props_table . ' 
					WHERE created_at <= %d
					GROUP BY person_id
				)
			ORDER BY
				pp.name',
			$competition_id,
			"%${query}%",
			"%${query}%",
			"%${query}%",
			"%${query}%",
			self::to_db_date( $date ?: new DateTime() ) );

		return $objects;
	}

	function anonymize( $group_ids = array(), $exclude_contacts = false ) {
		$anonymizer = new Anonymizer();
		$id         = new Id();

		if ( empty( $group_ids ) ) {
			throw new Exception( 'Must specify groups to anonymize.' );
		}

		$where = 'team_id IN (' . join( ', ', $group_ids ) . ')';
		if ( $exclude_contacts ) {
			$where .= ' AND is_team_contact != 1';
		}

		$current_names = array_map(
			function ( $row ) {
			return $row[0];
			},
			$this->wpdb->get_results( 'SELECT DISTINCT name FROM ' . $this->props_table . ' WHERE ' . $where, ARRAY_N )
		);
		foreach ( $current_names as $current_name ) {
			$this->wpdb->query(
				$this->wpdb->prepare(
				'UPDATE ' . $this->props_table . ' SET name = %s WHERE name = %s AND ' . $where,
				$anonymizer->first_name() . ' ' . $anonymizer->last_name(),
					$current_name
				)
			);
		}

		$current_phone_numbers = array_map(
			function ( $row ) {
			return $row[0];
			},
			$this->wpdb->get_results( 'SELECT DISTINCT phone FROM ' . $this->props_table . ' WHERE ' . $where, ARRAY_N )
		);
		foreach ( $current_phone_numbers as $current_phone_number ) {
			if ( ! empty( $current_phone_number ) ) {
				$this->wpdb->query(
					$this->wpdb->prepare(
					'UPDATE ' . $this->props_table . ' SET phone = %s WHERE phone = %s AND ' . $where,
					'0760-' . rand( 100000, 999999 ),
						$current_phone_number
					)
				);
			};
		}

		$current_email_addresses = array_map(
			function ( $row ) {
			return $row[0];
			},
			$this->wpdb->get_results( 'SELECT DISTINCT email FROM ' . $this->props_table . ' WHERE ' . $where, ARRAY_N )
		);
		foreach ( $current_email_addresses as $current_email_address ) {
			if ( ! empty( $current_email_address ) ) {
				$this->wpdb->query(
					$this->wpdb->prepare(
					'UPDATE ' . $this->props_table . ' SET email = %s WHERE email = %s AND ' . $where,
					$id->random_string( 5 ) . '@example.com',
						$current_email_address
					)
				);
			}
		}

		$current_pnos = array_map(
			function ( $row ) {
			return $row[0];
			},
			$this->wpdb->get_results( 'SELECT DISTINCT pno FROM ' . $this->props_table . ' WHERE ' . $where, ARRAY_N )
		);
		foreach ( $current_pnos as $current_pno ) {
			if ( ! empty( $current_pno ) ) {
				$this->wpdb->query(
					$this->wpdb->prepare(
					'UPDATE ' . $this->props_table . ' SET pno = %s WHERE pno = %s AND ' . $where,
					$anonymizer->birthdate( isset( $current_pno ) ? substr( $current_pno, 0, 4 ) : 2005 ) . '-0000',
						$current_pno
					)
				);
			}
		}

		$this->wpdb->query( 'UPDATE ' . $this->props_table . ' SET food = NULL WHERE ' . $where );
	}

	public function delete_by_key( $key ) {
		$person = $this->get_by_key( $key );

		return $person !== false && $this->add_record( $person, Person::STATUS_DELETED );
	}

	private static function to_person( $result ): Person {
		$p                 = new Person();
		$p->id             = intval( $result->person_id );
		$p->random_id      = $result->random_id;
		$p->name           = $result->name;
		$p->group_id       = intval( $result->team_id );
		$p->phone          = Phone::fix_phone_number( $result->phone ); // TODO: Should normalizing the phone number be something we do when we read it from the database? Why not when stored?
		$p->phone_verified = '1' === $result->phone_verified;
		$p->email          = $result->email;
		$p->email_verified = '1' === $result->email_verified;
		$p->note           = $result->note;
		$p->food           = $result->food;
		$p->pno            = $result->pno;
		$p->age            = floatval( $result->age );
		$p->set_status( $result->status );
		$p->set_role_flags(
			$result->is_competing != 0,
			$result->is_attending == null || $result->is_attending == 1,
			$result->is_team_contact != 0 );

		return $p;
	}

	private function get_group( Person $person ): Group {
		$group_dao = new GroupDao();

		if ( ! isset( $person->group_id ) ) {
			throw new Exception( "Group not assigned to person." );
		}

		$g = $group_dao->get( $person->group_id );
		if ( $g === false ) {
			throw new Exception( "Could not find person's group." );
		}

		return $g;
	}
}