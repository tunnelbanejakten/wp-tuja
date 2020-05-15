<?php


namespace tuja\util\rules;


use DateTimeImmutable;
use DateTimeZone;
use Exception;
use tuja\data\model\Competition;
use tuja\data\model\Person;
use tuja\util\DateRange;

abstract class RuleConfig {
	protected $slug;
	protected $label;

	public function __construct( string $slug, string $label ) {
		$this->slug  = $slug;
		$this->label = $label;
	}

	abstract function get_jsoneditor_prop_config();

	public function slug() {
		return $this->slug;
	}

	public function label() {
		return $this->label;
	}
}


class EnumRule extends RuleConfig {

	private $options;

	public function __construct( string $slug, string $label, array $options ) {
		parent::__construct( $slug, $label );
		$this->options = $options;
	}

	function get_jsoneditor_prop_config() {
		return [
			'title'    => $this->label,
			'required' => true,
			'type'     => 'string',
			'enum'     => array_keys( $this->options ),
			'options'  => [
				'enum_titles' => array_values( $this->options )
			]
		];

	}
}

class BoolRule extends RuleConfig {

	public function __construct( string $slug, string $label ) {
		parent::__construct( $slug, $label );
	}

	function get_jsoneditor_prop_config() {
		return [
			'title'    => $this->label,
			'required' => true,
			'type'     => 'boolean',
			'format'   => 'checkbox',
		];

	}
}

class DateRule extends RuleConfig {

	public function __construct( string $slug, string $label ) {
		parent::__construct( $slug, $label );
	}

	function get_jsoneditor_prop_config() {
		return
			[
				'title'    => $this->label,
				'required' => true,
				'type'     => 'integer',
				'format'   => 'date'
			];
	}
}

class IntRule extends RuleConfig {

	public function __construct( string $slug, string $label ) {
		parent::__construct( $slug, $label );
	}

	function get_jsoneditor_prop_config() {
		return [
			'title'    => $this->label,
			'required' => true,
			'type'     => 'integer',
			'format'   => 'number'
		];
	}
}

class GroupCategoryRules {

	private static $config;

	const BOOL_REQUIRED = 'required';
	const BOOL_OPTIONAL = 'optional';
	const BOOL_SKIP = 'skip';

	const LABELS = [
		'is_group_note_enabled' => 'Meddelande till tävlingsledning',
		'is_crew'               => 'Funktionärer',

		Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_COUNT_MIN     => 'Lagledare, min antal',
		Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_COUNT_MAX     => 'Lagledare, max antal',
		Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_PHONE         => 'Lagledare, telefon',
		Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_EMAIL         => 'Lagledare, e-post',
		Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_NAME          => 'Lagledare, namn',
		Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_NOTE          => 'Lagledare, meddelande',
		Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_FOOD          => 'Lagledare, matönskemål',
		Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_NIN           => 'Lagledare, personnr',
		Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_COUNT_MIN    => 'Lagmedlem, min antal',
		Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_COUNT_MAX    => 'Lagmedlem, max antal',
		Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_PHONE        => 'Lagmedlem, telefon',
		Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_EMAIL        => 'Lagmedlem, e-post',
		Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_NAME         => 'Lagmedlem, namn',
		Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_NOTE         => 'Lagmedlem, meddelande',
		Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_FOOD         => 'Lagmedlem, matönskemål',
		Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_NIN          => 'Lagmedlem, personnr',
		Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_COUNT_MIN => 'Medföljande vuxen, min antal',
		Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_COUNT_MAX => 'Medföljande vuxen, max antal',
		Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_PHONE     => 'Medföljande vuxen, telefon',
		Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_EMAIL     => 'Medföljande vuxen, e-post',
		Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_NAME      => 'Medföljande vuxen, namn',
		Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_NOTE      => 'Medföljande vuxen, meddelande',
		Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_FOOD      => 'Medföljande vuxen, matönskemål',
		Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_NIN       => 'Medföljande vuxen, personnr',
		Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_COUNT_MIN      => 'Administratör, min antal',
		Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_COUNT_MAX      => 'Administratör, max antal',
		Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_PHONE          => 'Administratör, telefon',
		Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_EMAIL          => 'Administratör, e-post',
		Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_NAME           => 'Administratör, namn',
		Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_NOTE           => 'Administratör, meddelande',
		Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_FOOD           => 'Administratör, matönskemål',
		Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_NIN            => 'Administratör, personnr',

		'create_registration_period_start' => 'Anmäla lag, fr.o.m.',
		'create_registration_period_end'   => 'Anmäla lag, t.o.m.',
		'update_registration_period_start' => 'Ändra lag, fr.o.m.',
		'update_registration_period_end'   => 'Ändra lag, t.o.m.',
		'delete_registration_period_start' => 'Avanmäla lag, fr.o.m.',
		'delete_registration_period_end'   => 'Avanmäla lag, t.o.m.',
		'delete_group_member_period_start' => 'Avanmäla person, fr.o.m.',
		'delete_group_member_period_end'   => 'Avanmäla person, t.o.m.',

		self::BOOL_REQUIRED                  => 'Obligatoriskt',
		self::BOOL_OPTIONAL                  => 'Valfritt',
		self::BOOL_SKIP                      => 'Visa inte',
		'nin_' . self::BOOL_REQUIRED         => 'Personnr obligatoriskt',
		'nin_' . self::BOOL_OPTIONAL         => 'Personnr valfritt',
		'nin_or_date_' . self::BOOL_REQUIRED => 'Personnr el. födelsedag oblig.',
		'nin_or_date_' . self::BOOL_OPTIONAL => 'Personnr el. födelsedag valfritt',
		'date_' . self::BOOL_REQUIRED        => 'Födelsedag obligatoriskt',
		'date_' . self::BOOL_OPTIONAL        => 'Födelsedag valfritt',
		'year_' . self::BOOL_REQUIRED        => 'Födelseår obligatoriskt',
		'year_' . self::BOOL_OPTIONAL        => 'Födelseår valfritt',
	];

	const TRISTATE_OPTIONS = [
		self::BOOL_REQUIRED => self::LABELS[ self::BOOL_REQUIRED ],
		self::BOOL_OPTIONAL => self::LABELS[ self::BOOL_OPTIONAL ],
		self::BOOL_SKIP     => self::LABELS[ self::BOOL_SKIP ]
	];

	const PERSON_PROP_COUNT_MIN = 'count_min';
	const PERSON_PROP_COUNT_MAX = 'count_max';
	const PERSON_PROP_PHONE = 'phone';
	const PERSON_PROP_EMAIL = 'email';
	const PERSON_PROP_NAME = 'name';
	const PERSON_PROP_NOTE = 'note';
	const PERSON_PROP_FOOD = 'food';
	const PERSON_PROP_NIN = 'nin';

	private $values;

	public function __construct( $values = [] ) {
		$this->values = $values;
	}

	private static function get_config() {
		if ( ! isset( self::$config ) ) {

			$enum_config       = function ( string $slug, string $name, array $options ) {
				return [ new EnumRule( $slug, $name, $options ) ];
			};
			$bool_config       = function ( string $slug, string $name ) {
				return [ new BoolRule( $slug, $name ) ];
			};
			$date_range_config = function ( string $slug, string $name ) {
				return [
					new DateRule( $slug . '_start', $name . ', fr.o.m.' ),
					new DateRule( $slug . '_end', $name . ', t.o.m.' )
				];
			};
			$int_config        = function ( string $slug, string $name ) {
				return [ new IntRule( $slug, $name ) ];
			};

			$person_types_configs = array_map( function ( string $slug ) use ( $int_config, $enum_config ) {
				return array_merge(
					$int_config( $slug . '_' . self::PERSON_PROP_COUNT_MIN, self::LABELS[ $slug . '_' . self::PERSON_PROP_COUNT_MIN ] ),
					$int_config( $slug . '_' . self::PERSON_PROP_COUNT_MAX, self::LABELS[ $slug . '_' . self::PERSON_PROP_COUNT_MAX ] ),
					$enum_config( $slug . '_' . self::PERSON_PROP_PHONE, self::LABELS[ $slug . '_' . self::PERSON_PROP_PHONE ], self::TRISTATE_OPTIONS ),
					$enum_config( $slug . '_' . self::PERSON_PROP_EMAIL, self::LABELS[ $slug . '_' . self::PERSON_PROP_EMAIL ], self::TRISTATE_OPTIONS ),
					$enum_config( $slug . '_' . self::PERSON_PROP_NAME, self::LABELS[ $slug . '_' . self::PERSON_PROP_NAME ], self::TRISTATE_OPTIONS ),
					$enum_config( $slug . '_' . self::PERSON_PROP_NOTE, self::LABELS[ $slug . '_' . self::PERSON_PROP_NOTE ], self::TRISTATE_OPTIONS ),
					$enum_config( $slug . '_' . self::PERSON_PROP_FOOD, self::LABELS[ $slug . '_' . self::PERSON_PROP_FOOD ], self::TRISTATE_OPTIONS ),
					$enum_config( $slug . '_' . self::PERSON_PROP_NIN, self::LABELS[ $slug . '_' . self::PERSON_PROP_NIN ], [
						'nin_' . self::BOOL_REQUIRED         => self::LABELS[ 'nin_' . self::BOOL_REQUIRED ],
						'nin_' . self::BOOL_OPTIONAL         => self::LABELS[ 'nin_' . self::BOOL_OPTIONAL ],
						'nin_or_date_' . self::BOOL_REQUIRED => self::LABELS[ 'nin_or_date_' . self::BOOL_REQUIRED ],
						'nin_or_date_' . self::BOOL_OPTIONAL => self::LABELS[ 'nin_or_date_' . self::BOOL_OPTIONAL ],
						'date_' . self::BOOL_REQUIRED        => self::LABELS[ 'date_' . self::BOOL_REQUIRED ],
						'date_' . self::BOOL_OPTIONAL        => self::LABELS[ 'date_' . self::BOOL_OPTIONAL ],
						'year_' . self::BOOL_REQUIRED        => self::LABELS[ 'year_' . self::BOOL_REQUIRED ],
						'year_' . self::BOOL_OPTIONAL        => self::LABELS[ 'year_' . self::BOOL_OPTIONAL ],
						self::BOOL_SKIP                      => self::LABELS[ self::BOOL_SKIP ]
					] ) );
			}, Person::PERSON_TYPES );
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
		return array_combine( array_map( function ( RuleConfig $conf ) {
			return $conf->slug();
		}, self::get_config() ), array_map( function ( RuleConfig $conf ) {
			return $conf->get_jsoneditor_prop_config();
		}, self::get_config() ) );
	}

	public function get_json_values() {
		return json_encode( $this->values );
	}

	public function get_values() {
		return $this->values;
	}

	public static function get_props_labels() {
		return array_map( function ( RuleConfig $config ) {
			return $config->label();
		}, self::get_config() );
	}

	public function get_people_count_range( string ...$person_types ): array {
//		if ( ! in_array( $person_type, self::PERSON_TYPES ) ) {
//			throw new Exception( 'Unsupported person type: ' . $person_type );
//		}

		return [
			array_sum( array_map( function ( string $person_type ) {
				return $this->values[ $person_type . '_' . self::PERSON_PROP_COUNT_MIN ] ?: 0;
			}, $person_types ) ),
			array_sum( array_map( function ( string $person_type ) {
				return $this->values[ $person_type . '_' . self::PERSON_PROP_COUNT_MAX ] ?: 0;
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
		return $this->get_people_count_range( Person::PERSON_TYPE_LEADER )[0] > 0;
	}

	public function is_contact_information_required_for_regular_group_member(): bool {
		return $this->values[ Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_EMAIL ] === self::BOOL_REQUIRED
		       || $this->values[ Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_PHONE ] === self::BOOL_REQUIRED;
	}

	public function is_adult_supervisor_required(): bool {
		return $this->get_people_count_range( Person::PERSON_TYPE_SUPERVISOR )[0] > 0;
	}

	public function is_ssn_required(): bool {
		return $this->values[ Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_NIN ] === 'nin_or_date_required'
		       || $this->values[ Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_NIN ] === 'nin_or_date_required';
	}

	public function is_person_note_enabled(): bool {
		return $this->values[ Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_NOTE ] !== self::BOOL_SKIP
		       || $this->values[ Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_NOTE ] !== self::BOOL_SKIP
		       || $this->values[ Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_NOTE ] !== self::BOOL_SKIP;
	}

	public function is_person_field_enabled( $person_type, $field ): bool {
		if ( ! in_array( $person_type, Person::PERSON_TYPES ) ) {
			throw new Exception( "Unsupported type of person: " . $person_type );
		}
		$rule_slug = $person_type . '_' . $field;

		return isset( $this->values[ $rule_slug ] ) ? @$this->values[ $rule_slug ] !== self::BOOL_SKIP : true;
	}

	public function is_person_field_required( $person_type, $field ): bool {
		if ( ! in_array( $person_type, Person::PERSON_TYPES ) ) {
			throw new Exception( "Unsupported type of person: " . $person_type );
		}
		$rule_slug = $person_type . '_' . $field;

		return isset( $this->values[ $rule_slug ] ) ? strpos( @$this->values[ $rule_slug ], self::BOOL_REQUIRED ) !== false : true;
	}


	public function is_group_note_enabled(): bool {
		return $this->values['is_group_note_enabled'] === true;
	}

	public function is_crew(): bool {
		return $this->values['is_crew'] === true;
	}

	public static function from_rule_set( RuleSet $rule_set, Competition $competition ): GroupCategoryRules {
		return new GroupCategoryRules( [
			Person::PERSON_TYPE_LEADER . "_" . self::PERSON_PROP_COUNT_MIN => $rule_set->is_group_leader_required() ? 1 : 0,
			Person::PERSON_TYPE_LEADER . "_" . self::PERSON_PROP_COUNT_MAX => $rule_set->is_group_leader_required() ? 1 : 0,
			Person::PERSON_TYPE_LEADER . "_" . self::PERSON_PROP_PHONE     => self::BOOL_REQUIRED,
			Person::PERSON_TYPE_LEADER . "_" . self::PERSON_PROP_EMAIL     => self::BOOL_REQUIRED,
			Person::PERSON_TYPE_LEADER . "_" . self::PERSON_PROP_NAME      => self::BOOL_REQUIRED,
			Person::PERSON_TYPE_LEADER . "_" . self::PERSON_PROP_NOTE      => $rule_set->is_person_note_enabled() ? self::BOOL_OPTIONAL : self::BOOL_SKIP,
			Person::PERSON_TYPE_LEADER . "_" . self::PERSON_PROP_FOOD      => self::BOOL_OPTIONAL,
			Person::PERSON_TYPE_LEADER . "_" . self::PERSON_PROP_NIN       => $rule_set->is_ssn_required() ? 'nin_or_date_required' : 'skip',

			Person::PERSON_TYPE_REGULAR . "_" . self::PERSON_PROP_COUNT_MIN => $rule_set->get_group_size_range()[0] - ( $rule_set->is_group_leader_required() ? 1 : 0 ),
			Person::PERSON_TYPE_REGULAR . "_" . self::PERSON_PROP_COUNT_MAX => $rule_set->get_group_size_range()[1] - ( $rule_set->is_group_leader_required() ? 1 : 0 ),
			Person::PERSON_TYPE_REGULAR . "_" . self::PERSON_PROP_PHONE     => $rule_set->is_contact_information_required_for_regular_group_member() ? self::BOOL_REQUIRED : self::BOOL_SKIP,
			Person::PERSON_TYPE_REGULAR . "_" . self::PERSON_PROP_EMAIL     => $rule_set->is_contact_information_required_for_regular_group_member() ? self::BOOL_REQUIRED : self::BOOL_SKIP,
			Person::PERSON_TYPE_REGULAR . "_" . self::PERSON_PROP_NAME      => self::BOOL_REQUIRED,
			Person::PERSON_TYPE_REGULAR . "_" . self::PERSON_PROP_NOTE      => $rule_set->is_person_note_enabled() ? self::BOOL_OPTIONAL : self::BOOL_SKIP,
			Person::PERSON_TYPE_REGULAR . "_" . self::PERSON_PROP_FOOD      => self::BOOL_OPTIONAL,
			Person::PERSON_TYPE_REGULAR . "_" . self::PERSON_PROP_NIN       => $rule_set->is_ssn_required() ? 'nin_or_date_required' : 'skip',

			Person::PERSON_TYPE_SUPERVISOR . "_" . self::PERSON_PROP_COUNT_MIN => $rule_set->is_adult_supervisor_required() ? 1 : 0,
			Person::PERSON_TYPE_SUPERVISOR . "_" . self::PERSON_PROP_COUNT_MAX => $rule_set->is_adult_supervisor_required() ? 3 : 0,
			Person::PERSON_TYPE_SUPERVISOR . "_" . self::PERSON_PROP_PHONE     => self::BOOL_REQUIRED,
			Person::PERSON_TYPE_SUPERVISOR . "_" . self::PERSON_PROP_EMAIL     => self::BOOL_REQUIRED,
			Person::PERSON_TYPE_SUPERVISOR . "_" . self::PERSON_PROP_NAME      => self::BOOL_SKIP,
			Person::PERSON_TYPE_SUPERVISOR . "_" . self::PERSON_PROP_NOTE      => $rule_set->is_person_note_enabled() ? self::BOOL_OPTIONAL : self::BOOL_SKIP,
			Person::PERSON_TYPE_SUPERVISOR . "_" . self::PERSON_PROP_FOOD      => self::BOOL_OPTIONAL,
			Person::PERSON_TYPE_SUPERVISOR . "_" . self::PERSON_PROP_NIN       => 'skip',

			Person::PERSON_TYPE_ADMIN . "_" . self::PERSON_PROP_COUNT_MIN => 0,
			Person::PERSON_TYPE_ADMIN . "_" . self::PERSON_PROP_COUNT_MAX => $rule_set->is_crew() ? 0 : 1,
			Person::PERSON_TYPE_ADMIN . "_" . self::PERSON_PROP_PHONE     => self::BOOL_SKIP,
			Person::PERSON_TYPE_ADMIN . "_" . self::PERSON_PROP_EMAIL     => self::BOOL_REQUIRED,
			Person::PERSON_TYPE_ADMIN . "_" . self::PERSON_PROP_NAME      => self::BOOL_SKIP,
			Person::PERSON_TYPE_ADMIN . "_" . self::PERSON_PROP_NOTE      => self::BOOL_SKIP,
			Person::PERSON_TYPE_ADMIN . "_" . self::PERSON_PROP_FOOD      => self::BOOL_SKIP,
			Person::PERSON_TYPE_ADMIN . "_" . self::PERSON_PROP_NIN       => 'skip',

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