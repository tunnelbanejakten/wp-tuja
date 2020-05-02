<?php


namespace tuja\frontend;


use Exception;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\util\rules\GroupCategoryRules;
use tuja\util\Strings;
use tuja\view\Field;
use tuja\view\FieldEmail;
use tuja\view\FieldPhone;
use tuja\view\FieldPno;
use tuja\view\FieldText;

class PersonForm {

	private $person_name_question;
	private $person_pno_question;
	private $person_email_question;
	private $person_phone_question;
	private $person_food_question;
	private $person_note_question;
	private $show_validation_errors;
	private $group_category_rules;

	const FIELD_NAME = 'name';
	const FIELD_PNO = 'pno';
	const FIELD_EMAIL = 'email';
	const FIELD_PHONE = 'phone';
	const FIELD_FOOD = 'food';
	const FIELD_NOTE = 'note';

	CONST VALIDATION_FIELD_MAPPING = [
		'email' => self::FIELD_EMAIL,
		'phone' => self::FIELD_PHONE,
		'name'  => self::FIELD_NAME,
		'food'  => self::FIELD_FOOD,
		'note'  => self::FIELD_NOTE,
		'pno'   => self::FIELD_PNO,
//		'status' => ???
	];

	public function __construct( bool $compact, bool $read_only, bool $show_validation_errors, GroupCategoryRules $group_category_rules, string $i18n_prefix = 'person.form' ) {
		$this->person_name_question   = new FieldText( Strings::get( "${i18n_prefix}.name.label" ), Strings::get( "${i18n_prefix}.name.hint" ), $read_only, [], $compact );
		$this->person_pno_question    = new FieldPno( Strings::get( "${i18n_prefix}.pno.label" ), Strings::get( "${i18n_prefix}.pno.hint" ), $read_only, $compact );
		$this->person_email_question  = new FieldEmail( Strings::get( "${i18n_prefix}.email.label" ), Strings::get( "${i18n_prefix}.email.hint" ), $read_only, $compact );
		$this->person_phone_question  = new FieldPhone( Strings::get( "${i18n_prefix}.phone.label" ), Strings::get( "${i18n_prefix}.phone.hint" ), $read_only, $compact );
		$this->person_food_question   = new FieldText( Strings::get( "${i18n_prefix}.food.label" ), Strings::get( "${i18n_prefix}.food.hint" ), $read_only, [], $compact );
		$this->person_note_question   = new FieldText( Strings::get( "${i18n_prefix}.note.label" ), Strings::get( "${i18n_prefix}.note.hint" ), $read_only, [], $compact );
		$this->show_validation_errors = $show_validation_errors;
		$this->group_category_rules   = $group_category_rules;
	}


	public function render( Person $person ) {
		$errors = [];
		if ( $this->show_validation_errors ) {
			try {
				$person->validate( $this->group_category_rules );
			} catch ( ValidationException $e ) {
				$field            = self::VALIDATION_FIELD_MAPPING[ $e->getField() ];
				$errors[ $field ] = $e->getMessage();
			}
		}
		try {
			$html_sections = [];
			if ( $this->group_category_rules->is_person_field_enabled( $person->get_type(), GroupCategoryRules::PERSON_PROP_NAME ) ) {
				$html_sections[] = FrontendView::render_field( $this->person_name_question, self::get_field_name( self::FIELD_NAME, $person ), @$errors[ self::FIELD_NAME ], $person->name );
			}
			if ( $this->group_category_rules->is_person_field_enabled( $person->get_type(), GroupCategoryRules::PERSON_PROP_NIN ) ) {
				$html_sections[] = FrontendView::render_field( $this->person_pno_question, self::get_field_name( self::FIELD_PNO, $person ), @$errors[ self::FIELD_PNO ], $person->pno );
			}
			if ( $this->group_category_rules->is_person_field_enabled( $person->get_type(), GroupCategoryRules::PERSON_PROP_EMAIL ) ) {
				$html_sections[] = FrontendView::render_field( $this->person_email_question, self::get_field_name( self::FIELD_EMAIL, $person ), @$errors[ self::FIELD_EMAIL ], $person->email );
			}
			if ( $this->group_category_rules->is_person_field_enabled( $person->get_type(), GroupCategoryRules::PERSON_PROP_PHONE ) ) {
				$html_sections[] = FrontendView::render_field( $this->person_phone_question, self::get_field_name( self::FIELD_PHONE, $person ), @$errors[ self::FIELD_PHONE ], $person->phone );
			}
			if ( $this->group_category_rules->is_person_field_enabled( $person->get_type(), GroupCategoryRules::PERSON_PROP_FOOD ) ) {
				$html_sections[] = FrontendView::render_field( $this->person_food_question, self::get_field_name( self::FIELD_FOOD, $person ), @$errors[ self::FIELD_FOOD ], $person->food );
			}
			if ( $this->group_category_rules->is_person_field_enabled( $person->get_type(), GroupCategoryRules::PERSON_PROP_NOTE ) ) {
				$html_sections[] = FrontendView::render_field( $this->person_note_question, self::get_field_name( self::FIELD_NOTE, $person ), @$errors[ self::FIELD_NOTE ], $person->note );
			}

			return join( $html_sections );
		} catch ( Exception $e ) {
			return sprintf( "[error %s]", $e->getMessage() );
		}

	}

	public static function get_field_name( string $field, Person $person ): string {
		return FrontendView::FIELD_PREFIX_PERSON . $field . '__' . ( $person->random_id ?: $person->id ?: '' );
	}
}