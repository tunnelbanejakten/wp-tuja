<?php


namespace tuja\util\rules;


use DateTimeImmutable;
use DateTimeZone;
use Error;
use Exception;
use tuja\data\model\Competition;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\util\DateRange;
use tuja\util\Strings;

abstract class RuleConfig {
	protected $label;
	public $value;
	public $description;

	public function __construct( string $label, string $description = '' ) {
		$this->label       = $label;
		$this->description = $description;
	}

	abstract function get_jsoneditor_prop_config();

	public function validate_input( $input ) {
		throw new Error( "$input is rejected by default." );
	}

	public function set_value( $value ) {
		$this->value = $value;
	}

	public function label() {
		return $this->label;
	}

	public function description() {
		return $this->description;
	}
}


class EnumRule extends RuleConfig {

	private $options;

	public function __construct( string $label, array $options ) {
		parent::__construct( $label );
		$this->options = $options;
	}

	public function set_value( $value ) {
		if ( ! in_array( $value, array_keys( $this->options ) ) ) {
			throw new Exception( "$value is not a valid setting." );
		}
		parent::set_value( $value );
	}

	public function validate_input( $input ) {
		$regexp = $this->options[ $this->value ]['validator'];
		if ( preg_match( "/$regexp/", $input ) !== 1 ) {
			throw new Exception( "$input ser inte rätt ut." );
		}
	}

	function get_jsoneditor_prop_config() {
		return array(
			'title'    => $this->label,
			'required' => true,
			'type'     => 'string',
			'enum'     => array_keys( $this->options ),
			'options'  => array(
				'enum_titles' => array_map(
					function ( $props ) {
						return $props['label'];
					},
					array_values( $this->options )
				),
			),
		);

	}
}

class BoolRule extends RuleConfig {

	public function __construct( string $label, string $description = '' ) {
		parent::__construct( $label, $description );
	}

	function get_jsoneditor_prop_config() {
		return array(
			'title'    => $this->label,
			'required' => true,
			'type'     => 'boolean',
			'format'   => 'checkbox',
		);

	}
}

class DateRule extends RuleConfig {

	public function __construct( string $label ) {
		parent::__construct( $label );
	}

	function get_jsoneditor_prop_config() {
		return
			array(
				'title'    => $this->label,
				'required' => true,
				'type'     => 'integer',
				'format'   => 'date',
			);
	}
}

class IntRule extends RuleConfig {

	public function __construct( string $label, string $description = '' ) {
		parent::__construct( $label, $description );
	}

	function get_jsoneditor_prop_config() {
		return array(
			'title'    => $this->label,
			'required' => true,
			'type'     => 'integer',
			'format'   => 'number',
		);
	}
}
class GroupCategoryRules {

	const YEAR_PATTERN = '(19|20)?[0-9]{2}';
	const DATE_PATTERN = self::YEAR_PATTERN . '-?(0[1-9]|[1-2][0-9])-?[0-3][0-9]';
	const PNO_PATTERN  = self::DATE_PATTERN . '-*[0-9]{4}';
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
	const PNO_OR_DATE_PATTERN = self::DATE_PATTERN . '(-*[0-9]{4})?';

	const PHONE_PATTERN = '\+?[0-9 -]{6,}';

	const EMAIL_PATTERN = '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}';

	const BOOL_REQUIRED = 'required';
	const BOOL_OPTIONAL = 'optional';
	const BOOL_SKIP     = 'skip';

	const TRISTATE_OPTIONS = array(
		self::BOOL_REQUIRED => array(
			'label'     => 'Obligatoriskt',
			'validator' => '.+',
		),
		self::BOOL_OPTIONAL => array(
			'label'     => 'Valfritt',
			'validator' => '.*',
		),
		self::BOOL_SKIP     => array(
			'label'     => 'Visa inte',
			'validator' => '.*',
		),
	);

	private static function tristate_options( string $validation_regexp ) {
		return array(
			self::BOOL_REQUIRED => array(
				'label'     => 'Obligatoriskt',
				'validator' => '^' . $validation_regexp . '$',
			),
			self::BOOL_OPTIONAL => array(
				'label'     => 'Valfritt',
				'validator' => '^(' . $validation_regexp . ')?$',
			),
			self::BOOL_SKIP     => array(
				'label'     => 'Visa inte',
				'validator' => '.*',
			),
		);
	}

	const NIN_OPTIONS = array(
		'nin_' . self::BOOL_REQUIRED         => array(
			'label'     => 'Personnr obligatoriskt',
			'validator' => '^' . self::PNO_PATTERN . '$',
		),
		'nin_' . self::BOOL_OPTIONAL         => array(
			'label'     => 'Personnr valfritt',
			'validator' => '^(' . self::PNO_PATTERN . ')?$',
		),
		'nin_or_date_' . self::BOOL_REQUIRED => array(
			'label'     => 'Personnr el. födelsedag oblig.',
			'validator' => '^' . self::PNO_OR_DATE_PATTERN . '$',
		),
		'nin_or_date_' . self::BOOL_OPTIONAL => array(
			'label'     => 'Personnr el. födelsedag valfritt',
			'validator' => '^(' . self::PNO_OR_DATE_PATTERN . ')?$',
		),
		'date_' . self::BOOL_REQUIRED        => array(
			'label'     => 'Födelsedag obligatoriskt',
			'validator' => '^' . self::DATE_PATTERN . '$',
		),
		'date_' . self::BOOL_OPTIONAL        => array(
			'label'     => 'Födelsedag valfritt',
			'validator' => '^(' . self::DATE_PATTERN . ')?$',
		),
		'year_' . self::BOOL_REQUIRED        => array(
			'label'     => 'Födelseår obligatoriskt',
			'validator' => '^' . self::YEAR_PATTERN . '$',
		),
		'year_' . self::BOOL_OPTIONAL        => array(
			'label'     => 'Födelseår valfritt',
			'validator' => '^(' . self::YEAR_PATTERN . ')?$',
		),
		self::BOOL_SKIP                      => array(
			'label'     => 'Visa inte',
			'validator' => '.*',
		),
	);

	const PERSON_PROP_COUNT_MIN = 'count_min';
	const PERSON_PROP_COUNT_MAX = 'count_max';
	const PERSON_PROP_PHONE     = 'phone';
	const PERSON_PROP_EMAIL     = 'email';
	const PERSON_PROP_NAME      = 'name';
	const PERSON_PROP_NOTE      = 'note';
	const PERSON_PROP_FOOD      = 'food';
	const PERSON_PROP_NIN       = 'nin';

	public function __construct( $values = array() ) {
		$this->is_group_note_enabled            = new BoolRule( 'Meddelande till tävlingsledning', 'Visa fältet "Meddelande till tävlingsledningen" för grupper som anmäls i kategorin.' );
		$this->is_crew                          = new BoolRule( 'Funktionärer', 'Grupper i kategorin, och personerna i grupperna, är funktionärer. Funktionärer har rätt att dela ut poäng.' );
		$this->time_limit_multiplier            = new IntRule( 'Tidshandikappfaktor', 'Anpassaar tidsgränsen för tidsbegränsade uppgifter. Värdet 100 betyder att laget får så lång tid som angetts för frågan. Värdet 150 betyder att laget får 50% mer tid än så. Värdet 75 betyder att laget bara får 75 % av tiden som angetts.' );
		$this->leader_count_min                 = new IntRule( 'Lagledare, min antal' );
		$this->leader_count_max                 = new IntRule( 'Lagledare, max antal' );
		$this->leader_phone                     = new EnumRule( 'Lagledare, telefon', self::TRISTATE_OPTIONS );
		$this->leader_email                     = new EnumRule( 'Lagledare, e-post', self::tristate_options( self::EMAIL_PATTERN ) );
		$this->leader_name                      = new EnumRule( 'Lagledare, namn', self::TRISTATE_OPTIONS );
		$this->leader_note                      = new EnumRule( 'Lagledare, meddelande', self::TRISTATE_OPTIONS );
		$this->leader_food                      = new EnumRule( 'Lagledare, matönskemål', self::TRISTATE_OPTIONS );
		$this->leader_nin                       = new EnumRule( 'Lagledare, personnr', self::NIN_OPTIONS );
		$this->regular_count_min                = new IntRule( 'Lagmedlem, min antal' );
		$this->regular_count_max                = new IntRule( 'Lagmedlem, max antal' );
		$this->regular_phone                    = new EnumRule( 'Lagmedlem, telefon', self::TRISTATE_OPTIONS );
		$this->regular_email                    = new EnumRule( 'Lagmedlem, e-post', self::tristate_options( self::EMAIL_PATTERN ) );
		$this->regular_name                     = new EnumRule( 'Lagmedlem, namn', self::TRISTATE_OPTIONS );
		$this->regular_note                     = new EnumRule( 'Lagmedlem, meddelande', self::TRISTATE_OPTIONS );
		$this->regular_food                     = new EnumRule( 'Lagmedlem, matönskemål', self::TRISTATE_OPTIONS );
		$this->regular_nin                      = new EnumRule( 'Lagmedlem, personnr', self::NIN_OPTIONS );
		$this->supervisor_count_min             = new IntRule( 'Medföljande vuxen, min antal' );
		$this->supervisor_count_max             = new IntRule( 'Medföljande vuxen, max antal' );
		$this->supervisor_phone                 = new EnumRule( 'Medföljande vuxen, telefon', self::TRISTATE_OPTIONS );
		$this->supervisor_email                 = new EnumRule( 'Medföljande vuxen, e-post', self::tristate_options( self::EMAIL_PATTERN ) );
		$this->supervisor_name                  = new EnumRule( 'Medföljande vuxen, namn', self::TRISTATE_OPTIONS );
		$this->supervisor_note                  = new EnumRule( 'Medföljande vuxen, meddelande', self::TRISTATE_OPTIONS );
		$this->supervisor_food                  = new EnumRule( 'Medföljande vuxen, matönskemål', self::TRISTATE_OPTIONS );
		$this->supervisor_nin                   = new EnumRule( 'Medföljande vuxen, personnr', self::NIN_OPTIONS );
		$this->admin_count_min                  = new IntRule( 'Administratör, min antal' );
		$this->admin_count_max                  = new IntRule( 'Administratör, max antal' );
		$this->admin_phone                      = new EnumRule( 'Administratör, telefon', self::TRISTATE_OPTIONS );
		$this->admin_email                      = new EnumRule( 'Administratör, e-post', self::tristate_options( self::EMAIL_PATTERN ) );
		$this->admin_name                       = new EnumRule( 'Administratör, namn', self::TRISTATE_OPTIONS );
		$this->admin_note                       = new EnumRule( 'Administratör, meddelande', self::TRISTATE_OPTIONS );
		$this->admin_food                       = new EnumRule( 'Administratör, matönskemål', self::TRISTATE_OPTIONS );
		$this->admin_nin                        = new EnumRule( 'Administratör, personnr', self::NIN_OPTIONS );
		$this->create_registration_period_start = new DateRule( 'Anmäla lag, fr.o.m.' );
		$this->create_registration_period_end   = new DateRule( 'Anmäla lag, t.o.m.' );
		$this->update_registration_period_start = new DateRule( 'Ändra lag, fr.o.m.' );
		$this->update_registration_period_end   = new DateRule( 'Ändra lag, t.o.m.' );
		$this->delete_registration_period_start = new DateRule( 'Avanmäla lag, fr.o.m.' );
		$this->delete_registration_period_end   = new DateRule( 'Avanmäla lag, t.o.m.' );
		$this->delete_group_member_period_start = new DateRule( 'Avanmäla person, fr.o.m.' );
		$this->delete_group_member_period_end   = new DateRule( 'Avanmäla person, t.o.m.' );

		foreach ( $values as $prop => $value ) {
			$this->{$prop}->set_value( $value );
		}
	}

	private function get_config() {
		$vars = get_object_vars( $this );
		return $vars;
	}

	public function get_jsoneditor_config(): string {
		return json_encode(
			array(
				'type'       => 'object',
				'properties' => $this->get_jsoneditor_props_config(),
			)
		);
	}

	private function get_jsoneditor_props_config(): array {
		$config = $this->get_config();
		return array_combine(
			array_keys( $config ),
			array_map(
				function ( RuleConfig $conf ) {
					return $conf->get_jsoneditor_prop_config();
				},
				array_values( $this->get_config() )
			)
		);
	}

	public function get_json_values() {
		return json_encode( $this->get_values() );
	}

	public function get_values() {
		return array_map(
			function ( RuleConfig $config ) {
				return $config->value;
			},
			$this->get_config()
		);
	}

	public function get_props_labels() {
		return array_map(
			function ( RuleConfig $config ) {
				return array( $config->label(), $config->description() );
			},
			$this->get_config()
		);
	}

	function get_time_limit_multiplier(): int {
		$value = $this->time_limit_multiplier->value;
		return is_int( $value ) && $value > 0 ? $value : 100;
	}

	public function get_people_count_range( string ...$person_types ): array {
		//      if ( ! in_array( $person_type, self::PERSON_TYPES ) ) {
		//          throw new Exception( 'Unsupported person type: ' . $person_type );
		//      }

		return array(
			array_sum(
				array_map(
					function ( string $person_type ) {
						return $this->{$person_type . '_' . self::PERSON_PROP_COUNT_MIN}->value ?: 0;
					},
					$person_types
				)
			),
			array_sum(
				array_map(
					function ( string $person_type ) {
						return $this->{$person_type . '_' . self::PERSON_PROP_COUNT_MAX}->value ?: 0;
					},
					$person_types
				)
			),
		);
	}

	private function get_date_range( string $slug ): DateRange {
		return new DateRange(
			self::from_unix_date(
				isset( $this->{$slug . '_start' }->value )
				? $this->{$slug . '_start' }->value
				: null
			),
			self::from_unix_date(
				isset( $this->{$slug . '_end' }->value )
				? ( $this->{$slug . '_end' }->value + ( 24 * 60 * 60 ) )
				: null
			)
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
		return self::BOOL_REQUIRED === $this->regular_email->value
			|| self::BOOL_REQUIRED === $this->regular_phone->value;
	}

	public function is_adult_supervisor_required(): bool {
		return $this->get_people_count_range( Person::PERSON_TYPE_SUPERVISOR )[0] > 0;
	}

	public function is_ssn_required(): bool {
		return 'nin_or_date_required' === $this->leader_nin->value
			|| 'nin_or_date_required' === $this->regular_nin->value;
	}

	public function is_person_note_enabled(): bool {
		return self::BOOL_SKIP !== $this->leader_note->value
			|| self::BOOL_SKIP !== $this->regular_note->value
			|| self::BOOL_SKIP !== $this->supervisor_note->value;
	}

	public function is_person_field_enabled( $person_type, $field ): bool {
		$value = $this->get_person_field_value( $person_type, $field );
		return self::BOOL_SKIP !== $value;
	}

	public function is_person_field_required( $person_type, $field ): bool {
		$value = $this->get_person_field_value( $person_type, $field );
		return isset( $value ) ? strpos( $value, self::BOOL_REQUIRED ) !== false : true;
	}

	public function get_person_field_value( $person_type, $field ) {
		if ( ! in_array( $person_type, Person::PERSON_TYPES ) ) {
			throw new Exception( "Unsupported type of person: $person_type" );
		}
		$rule_prop_name = $person_type . '_' . $field;

		return $this->{$rule_prop_name}->value;
	}

	public function is_group_note_enabled(): bool {
		return true === $this->is_group_note_enabled->value;
	}

	public function validate_person( Person $person ) {
		$mapper = array(
			self::PERSON_PROP_PHONE => 'phone',
			self::PERSON_PROP_EMAIL => 'email',
			self::PERSON_PROP_NAME  => 'name',
			self::PERSON_PROP_NOTE  => 'note',
			self::PERSON_PROP_FOOD  => 'food',
			self::PERSON_PROP_NIN   => 'pno',
		);
		foreach ( $mapper as $rule_prop_suffix => $person_prop ) {
			$rule_prop_name = sprintf( '%s_%s', $person->get_type(), $rule_prop_suffix );
			try {
				$this->{$rule_prop_name}->validate_input( $person->{$person_prop} ?? '' );
			} catch ( Exception $e ) {
				throw new ValidationException( $person_prop, Strings::get( "group_category_rules.validation_error.$person_prop" ) );
			}
		}
	}

	public function is_crew(): bool {
		return true === $this->is_crew->value;
	}

	public static function from_rule_set( RuleSet $rule_set, Competition $competition ): GroupCategoryRules {
		return new GroupCategoryRules(
			array(
				Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_COUNT_MIN => $rule_set->is_group_leader_required() ? 1 : 0,
				Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_COUNT_MAX => $rule_set->is_group_leader_required() ? 1 : 0,
				Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_PHONE => self::BOOL_REQUIRED,
				Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_EMAIL => self::BOOL_REQUIRED,
				Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_NAME => self::BOOL_REQUIRED,
				Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_NOTE => $rule_set->is_person_note_enabled() ? self::BOOL_OPTIONAL : self::BOOL_SKIP,
				Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_FOOD => self::BOOL_OPTIONAL,
				Person::PERSON_TYPE_LEADER . '_' . self::PERSON_PROP_NIN => $rule_set->is_ssn_required() ? 'nin_or_date_required' : 'skip',

				Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_COUNT_MIN => $rule_set->get_group_size_range()[0] - ( $rule_set->is_group_leader_required() ? 1 : 0 ),
				Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_COUNT_MAX => $rule_set->get_group_size_range()[1] - ( $rule_set->is_group_leader_required() ? 1 : 0 ),
				Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_PHONE => $rule_set->is_contact_information_required_for_regular_group_member() ? self::BOOL_REQUIRED : self::BOOL_SKIP,
				Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_EMAIL => $rule_set->is_contact_information_required_for_regular_group_member() ? self::BOOL_REQUIRED : self::BOOL_SKIP,
				Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_NAME => self::BOOL_REQUIRED,
				Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_NOTE => $rule_set->is_person_note_enabled() ? self::BOOL_OPTIONAL : self::BOOL_SKIP,
				Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_FOOD => self::BOOL_OPTIONAL,
				Person::PERSON_TYPE_REGULAR . '_' . self::PERSON_PROP_NIN => $rule_set->is_ssn_required() ? 'nin_or_date_required' : 'skip',

				Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_COUNT_MIN => $rule_set->is_adult_supervisor_required() ? 1 : 0,
				Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_COUNT_MAX => $rule_set->is_adult_supervisor_required() ? 3 : 0,
				Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_PHONE => self::BOOL_REQUIRED,
				Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_EMAIL => self::BOOL_REQUIRED,
				Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_NAME => self::BOOL_SKIP,
				Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_NOTE => $rule_set->is_person_note_enabled() ? self::BOOL_OPTIONAL : self::BOOL_SKIP,
				Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_FOOD => self::BOOL_OPTIONAL,
				Person::PERSON_TYPE_SUPERVISOR . '_' . self::PERSON_PROP_NIN => 'skip',

				Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_COUNT_MIN => 0,
				Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_COUNT_MAX => $rule_set->is_crew() ? 0 : 1,
				Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_PHONE => self::BOOL_SKIP,
				Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_EMAIL => self::BOOL_REQUIRED,
				Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_NAME => self::BOOL_SKIP,
				Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_NOTE => self::BOOL_SKIP,
				Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_FOOD => self::BOOL_SKIP,
				Person::PERSON_TYPE_ADMIN . '_' . self::PERSON_PROP_NIN => 'skip',

				'is_group_note_enabled'            => $rule_set->is_group_note_enabled(),
				'is_crew'                          => $rule_set->is_crew(),
				'time_limit_multiplier'            => $rule_set->get_time_limit_multiplier(),

				'create_registration_period_start' => $rule_set->get_create_registration_period( $competition )->getStartDate()->getTimestamp(),
				'create_registration_period_end'   => $rule_set->get_create_registration_period( $competition )->getEndDate()->getTimestamp(),
				'update_registration_period_start' => $rule_set->get_update_registration_period( $competition )->getStartDate()->getTimestamp(),
				'update_registration_period_end'   => $rule_set->get_update_registration_period( $competition )->getEndDate()->getTimestamp(),
				'delete_registration_period_start' => $rule_set->get_delete_registration_period( $competition )->getStartDate()->getTimestamp(),
				'delete_registration_period_end'   => $rule_set->get_delete_registration_period( $competition )->getEndDate()->getTimestamp(),
				'delete_group_member_period_start' => $rule_set->get_delete_group_member_period( $competition )->getStartDate()->getTimestamp(),
				'delete_group_member_period_end'   => $rule_set->get_delete_group_member_period( $competition )->getEndDate()->getTimestamp(),
			)
		);
	}
}
