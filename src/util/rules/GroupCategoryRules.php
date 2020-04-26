<?php


namespace tuja\util\rules;


use DateTimeImmutable;
use DateTimeZone;
use Exception;
use tuja\data\model\Competition;
use tuja\util\DateRange;

class GroupCategoryRules {

	private static $config;

	const PERSON_TYPE_LEADER = 'leader';
	const PERSON_TYPE_REGULAR = 'regular';
	const PERSON_TYPE_SUPERVISOR = 'supervisor';
	const PERSON_TYPE_ADMIN = 'admin';

	const PERSON_TYPES = [
		self::PERSON_TYPE_LEADER,
		self::PERSON_TYPE_REGULAR,
		self::PERSON_TYPE_SUPERVISOR,
		self::PERSON_TYPE_ADMIN
	];
	const BOOL_REQUIRED = 'required';
	const BOOL_OPTIONAL = 'optional';
	const BOOL_SKIP = 'skip';

	const LABELS = [
		'is_group_note_enabled' => 'Meddelande till tävlingsledning',
		'is_crew'               => 'Funktionärer',
		'leader_count_min'      => 'Lagledare, min antal',
		'leader_count_max'      => 'Lagledare, max antal',
		'leader_phone'          => 'Lagledare, telefon',
		'leader_email'          => 'Lagledare, e-post',
		'leader_name'           => 'Lagledare, namn',
		'leader_note'           => 'Lagledare, meddelande',
		'leader_nin'            => 'Lagledare, personnr',
		'regular_count_min'     => 'Lagmedlem, min antal',
		'regular_count_max'     => 'Lagmedlem, max antal',
		'regular_phone'         => 'Lagmedlem, telefon',
		'regular_email'         => 'Lagmedlem, e-post',
		'regular_name'          => 'Lagmedlem, namn',
		'regular_note'          => 'Lagmedlem, meddelande',
		'regular_nin'           => 'Lagmedlem, personnr',
		'supervisor_count_min'  => 'Medföljande vuxen, min antal',
		'supervisor_count_max'  => 'Medföljande vuxen, max antal',
		'supervisor_phone'      => 'Medföljande vuxen, telefon',
		'supervisor_email'      => 'Medföljande vuxen, e-post',
		'supervisor_name'       => 'Medföljande vuxen, namn',
		'supervisor_note'       => 'Medföljande vuxen, meddelande',
		'supervisor_nin'        => 'Medföljande vuxen, personnr',
		'admin_count_min'       => 'Administratör, min antal',
		'admin_count_max'       => 'Administratör, max antal',
		'admin_phone'           => 'Administratör, telefon',
		'admin_email'           => 'Administratör, e-post',
		'admin_name'            => 'Administratör, namn',
		'admin_note'            => 'Administratör, meddelande',
		'admin_nin'             => 'Administratör, personnr',

		'create_registration_period_start' => 'Anmäla lag, fr.o.m.',
		'create_registration_period_end'   => 'Anmäla lag, t.o.m.',
		'update_registration_period_start' => 'Ändra lag, fr.o.m.',
		'update_registration_period_end'   => 'Ändra lag, t.o.m.',
		'delete_registration_period_start' => 'Avanmäla lag, fr.o.m.',
		'delete_registration_period_end'   => 'Avanmäla lag, t.o.m.',
		'delete_group_member_period_start' => 'Avanmäla person, fr.o.m.',
		'delete_group_member_period_end'   => 'Avanmäla person, t.o.m.',

		'required'             => 'Obligatoriskt',
		'optional'             => 'Valfritt',
		'skip'                 => 'Visa inte',
		'nin_required'         => 'Personnr obligatoriskt',
		'nin_optional'         => 'Personnr valfritt',
		'nin_or_date_required' => 'Personnr el. födelsedag oblig.',
		'nin_or_date_optional' => 'Personnr el. födelsedag valfritt',
		'date_required'        => 'Födelsedag obligatoriskt',
		'date_optional'        => 'Födelsedag valfritt',
		'year_required'        => 'Födelseår obligatoriskt',
		'year_optional'        => 'Födelseår valfritt',
	];

	const TRISTATE_OPTIONS = [
		self::BOOL_REQUIRED => self::LABELS[ self::BOOL_REQUIRED ],
		self::BOOL_OPTIONAL => self::LABELS[ self::BOOL_OPTIONAL ],
		self::BOOL_SKIP     => self::LABELS[ self::BOOL_SKIP ]
	];

	private $values;

	public function __construct( $values ) {
		$this->values = $values; // TODO: Validate input and initialise default values
	}

	private static function get_config() {
		if ( ! isset( self::$config ) ) {

			$enum_config       = function ( string $slug, string $name, array $options ) {
				return [ [ 'type' => 'enum', 'slug' => $slug, 'name' => $name, 'options' => $options ] ];
			};
			$bool_config       = function ( string $slug, string $name ) {
				return [ [ 'type' => 'boolean', 'slug' => $slug, 'name' => $name ] ];
			};
			$date_range_config = function ( string $slug, string $name ) {
				return [
					[ 'type' => 'date', 'slug' => $slug . '_start', 'name' => $name . ', fr.o.m.' ],
					[ 'type' => 'date', 'slug' => $slug . '_end', 'name' => $name . ', t.o.m.' ]
				];
			};
			$int_config        = function ( string $slug, string $name ) {
				return [ [ 'type' => 'integer', 'slug' => $slug, 'name' => $name ] ];
			};

			$person_types_configs = array_map( function ( string $slug ) use ( $int_config, $enum_config ) {
				return array_merge(
					$int_config( "${slug}_count_min", $slug . ', min antal' ),
					$int_config( "${slug}_count_max", $slug . ', max antal' ),
					$enum_config( "${slug}_phone", $slug . ', telefon', self::TRISTATE_OPTIONS ),
					$enum_config( "${slug}_email", $slug . ', e-post', self::TRISTATE_OPTIONS ),
					$enum_config( "${slug}_name", $slug . ', namn', self::TRISTATE_OPTIONS ),
					$enum_config( "${slug}_note", $slug . ', meddelande', self::TRISTATE_OPTIONS ),
					$enum_config( "${slug}_nin", $slug . ', personnr', [
						'nin_required'         => self::LABELS['nin_required'],
						'nin_optional'         => self::LABELS['nin_optional'],
						'nin_or_date_required' => self::LABELS['nin_or_date_required'],
						'nin_or_date_optional' => self::LABELS['nin_or_date_optional'],
						'date_required'        => self::LABELS['date_required'],
						'date_optional'        => self::LABELS['date_optional'],
						'year_required'        => self::LABELS['year_required'],
						'year_optional'        => self::LABELS['year_optional'],
						'skip'                 => self::LABELS['skip']
					] ) );
			}, self::PERSON_TYPES );
			self::$config         = array_merge(
				$bool_config( 'is_group_note_enabled', 'Meddelande till tävlingsledning' ),
				$bool_config( 'is_crew', 'Funktionärer' ),
				$date_range_config( 'create_registration_period', 'Anmäla lag' ),
				$date_range_config( 'update_registration_period', 'Ändra lag' ),
				$date_range_config( 'delete_registration_period', 'Avanmäla lag' ),
				$date_range_config( 'delete_group_member_period', 'Avanmäla person' ),
				...$person_types_configs );
		}

		return self::$config;
	}

	public static function get_jsoneditor_config(): string {
		return json_encode( [
			'type'       => 'object',
			'properties' => self::get_jsoneditor_props_config()
		] );
	}

	private static function get_jsoneditor_props_config(): array {
		return array_combine( array_map( function ( $conf ) {
			return $conf['slug'];
		}, self::get_config() ), array_map( function ( $conf ) {
			$slug = $conf['slug'];
			switch ( $conf['type'] ) {
				case 'boolean':
					return [
						'title'    => self::LABELS[ $slug ] ?: $slug,
						'required' => true,
						'type'     => 'boolean',
						'format'   => 'checkbox',
					];
				case 'enum':
					return [
						'title'    => self::LABELS[ $slug ] ?: $slug,
						'required' => true,
						'type'     => 'string',
						'enum'     => array_keys( $conf['options'] ),
						'options'  => [
							'enum_titles' => array_values( $conf['options'] )
						]
					];
				case 'integer':
					return [
						'title'    => self::LABELS[ $slug ] ?: $slug,
						'required' => true,
						'type'     => 'integer',
						'format'   => 'number'
					];
				case 'date':
					return
						[
							'title'    => self::LABELS[ $slug ] ?: $slug,
							'required' => true,
							'type'     => 'integer',
							'format'   => 'date'
						];
				default:
					throw new Exception( 'Unsupported type' );
			}
		}, self::get_config() ) );
	}

	public function get_json_values() {
		return json_encode( $this->values );
	}

	public function get_values() {
		return $this->values;
	}

	public static function get_properties() {
		return array_values( array_map( function ( $props ) {
			return $props['title'];
		}, self::get_jsoneditor_props_config() ) );
	}

	public function get_people_count_range( string ...$person_types ): array {
//		if ( ! in_array( $person_type, self::PERSON_TYPES ) ) {
//			throw new Exception( 'Unsupported person type: ' . $person_type );
//		}

		return [
			array_sum( array_map( function ( string $person_type ) {
				return $this->values[ $person_type . '_count_min' ] ?: 0;
			}, $person_types ) ),
			array_sum( array_map( function ( string $person_type ) {
				return $this->values[ $person_type . '_count_max' ] ?: 0;
			}, $person_types ) ),
		];
	}

	private function get_date_range( string $slug ): DateRange {
		return new DateRange(
			self::from_unix_date( @$this->values[ $slug . '_start' ] ),
			self::from_unix_date( @$this->values[ $slug . '_end' ] )
		);
	}

	private static function from_unix_date( $timestamp ) {
		if ( ! empty( $timestamp ) ) {
			return new DateTimeImmutable( '@' . $timestamp, new DateTimeZone( 'UTC' ) );
		} else {
			return null;
		}
	}

	public function is_create_registration_allowed(): bool {
		return $this->get_date_range( 'create_registration_period' )->is_now();
	}

	public function is_update_registration_allowed(): bool {
		return $this->get_date_range( 'update_registration_period' )->is_now();
	}

	public function is_delete_registration_allowed(): bool {
		return $this->get_date_range( 'delete_registration_period' )->is_now();
	}

	public function is_delete_group_member_allowed(): bool {
		return $this->get_date_range( 'delete_group_member_period' )->is_now();
	}

	public function is_group_leader_required(): bool {
		return $this->get_people_count_range( self::PERSON_TYPE_LEADER )[0] > 0;
	}

	public function is_contact_information_required_for_regular_group_member(): bool {
		return $this->values[ self::PERSON_TYPE_REGULAR . '_email' ] === self::BOOL_REQUIRED
		       || $this->values[ self::PERSON_TYPE_REGULAR . '_phone' ] === self::BOOL_REQUIRED;
	}

	public function is_adult_supervisor_required(): bool {
		return $this->get_people_count_range( self::PERSON_TYPE_SUPERVISOR )[0] > 0;
	}

	public function is_ssn_required(): bool {
		return $this->values[ self::PERSON_TYPE_LEADER . '_nin' ] === 'nin_or_date_required'
		       || $this->values[ self::PERSON_TYPE_REGULAR . '_nin' ] === 'nin_or_date_required';
	}

	public function is_person_note_enabled(): bool {
		return $this->values[ self::PERSON_TYPE_LEADER . '_note' ] !== self::BOOL_SKIP
		       || $this->values[ self::PERSON_TYPE_REGULAR . '_note' ] !== self::BOOL_SKIP
		       || $this->values[ self::PERSON_TYPE_SUPERVISOR . '_note' ] !== self::BOOL_SKIP;
	}

	public function is_group_note_enabled(): bool {
		return $this->values['is_group_note_enabled'] === true;
	}

	public function is_crew(): bool {
		return $this->values['is_crew'] === true;
	}

	public static function from_rule_set( RuleSet $rule_set, Competition $competition ): GroupCategoryRules {
		return new GroupCategoryRules( [
			self::PERSON_TYPE_LEADER . "_count_min" => $rule_set->is_group_leader_required() ? 1 : 0,
			self::PERSON_TYPE_LEADER . "_count_max" => $rule_set->is_group_leader_required() ? 1 : 0,
			self::PERSON_TYPE_LEADER . "_phone"     => self::BOOL_REQUIRED,
			self::PERSON_TYPE_LEADER . "_email"     => self::BOOL_REQUIRED,
			self::PERSON_TYPE_LEADER . "_name"      => self::BOOL_REQUIRED,
			self::PERSON_TYPE_LEADER . "_note"      => $rule_set->is_person_note_enabled() ? self::BOOL_OPTIONAL : self::BOOL_SKIP,
			self::PERSON_TYPE_LEADER . "_nin"       => $rule_set->is_ssn_required() ? 'nin_or_date_required' : 'skip',

			self::PERSON_TYPE_REGULAR . "_count_min" => $rule_set->get_group_size_range()[0] - ( $rule_set->is_group_leader_required() ? 1 : 0 ),
			self::PERSON_TYPE_REGULAR . "_count_max" => $rule_set->get_group_size_range()[1] - ( $rule_set->is_group_leader_required() ? 1 : 0 ),
			self::PERSON_TYPE_REGULAR . "_phone"     => $rule_set->is_contact_information_required_for_regular_group_member() ? self::BOOL_REQUIRED : self::BOOL_SKIP,
			self::PERSON_TYPE_REGULAR . "_email"     => $rule_set->is_contact_information_required_for_regular_group_member() ? self::BOOL_REQUIRED : self::BOOL_SKIP,
			self::PERSON_TYPE_REGULAR . "_name"      => self::BOOL_REQUIRED,
			self::PERSON_TYPE_REGULAR . "_note"      => $rule_set->is_person_note_enabled() ? self::BOOL_OPTIONAL : self::BOOL_SKIP,
			self::PERSON_TYPE_REGULAR . "_nin"       => $rule_set->is_ssn_required() ? 'nin_or_date_required' : 'skip',

			self::PERSON_TYPE_SUPERVISOR . "_count_min" => $rule_set->is_adult_supervisor_required() ? 1 : 0,
			self::PERSON_TYPE_SUPERVISOR . "_count_max" => $rule_set->is_adult_supervisor_required() ? 3 : 0,
			self::PERSON_TYPE_SUPERVISOR . "_phone"     => self::BOOL_REQUIRED,
			self::PERSON_TYPE_SUPERVISOR . "_email"     => self::BOOL_REQUIRED,
			self::PERSON_TYPE_SUPERVISOR . "_name"      => self::BOOL_OPTIONAL,
			self::PERSON_TYPE_SUPERVISOR . "_note"      => $rule_set->is_person_note_enabled() ? self::BOOL_OPTIONAL : self::BOOL_SKIP,
			self::PERSON_TYPE_SUPERVISOR . "_nin"       => 'skip',

			self::PERSON_TYPE_ADMIN . "_count_min" => 0,
			self::PERSON_TYPE_ADMIN . "_count_max" => 1,
			self::PERSON_TYPE_ADMIN . "_phone"     => self::BOOL_OPTIONAL,
			self::PERSON_TYPE_ADMIN . "_email"     => self::BOOL_REQUIRED,
			self::PERSON_TYPE_ADMIN . "_name"      => self::BOOL_SKIP,
			self::PERSON_TYPE_ADMIN . "_note"      => self::BOOL_SKIP,
			self::PERSON_TYPE_ADMIN . "_nin"       => 'skip',

			'is_group_note_enabled' => $rule_set->is_group_note_enabled(),
			'is_crew'               => $rule_set->is_crew(),

			'create_registration_period_start' => $rule_set->get_create_registration_period( $competition )->getStartDate()->getTimestamp(),
			'create_registration_period_end'   => $rule_set->get_create_registration_period( $competition )->getEndDate()->getTimestamp(),
			'update_registration_period_start' => $rule_set->get_update_registration_period( $competition )->getStartDate()->getTimestamp(),
			'update_registration_period_end'   => $rule_set->get_update_registration_period( $competition )->getEndDate()->getTimestamp(),
			'delete_registration_period_start' => $rule_set->get_delete_registration_period( $competition )->getStartDate()->getTimestamp(),
			'delete_registration_period_end'   => $rule_set->get_delete_registration_period( $competition )->getEndDate()->getTimestamp(),
			'delete_group_member_period_start' => $rule_set->get_delete_group_member_period( $competition )->getStartDate()->getTimestamp(),
			'delete_group_member_period_end'   => $rule_set->get_delete_group_member_period( $competition )->getEndDate()->getTimestamp(),
		] );
	}
}