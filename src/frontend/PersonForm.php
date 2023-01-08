<?php


namespace tuja\frontend;


use Exception;
use tuja\data\model\Person;
use tuja\data\model\Group;
use tuja\data\model\ValidationException;
use tuja\util\rules\GroupCategoryRules;
use tuja\util\Strings;
use tuja\view\Field;
use tuja\view\FieldEmail;
use tuja\view\FieldFood;
use tuja\view\FieldPhone;
use tuja\view\FieldText;

class PersonForm {

	private $person_name_question;
	private $person_pno_questions;
	private $person_email_question;
	private $person_phone_question;
	private $person_food_questions;
	private $person_note_question;
	private $show_validation_errors;
	private $group_category_rules;
	private $required_only;

	const FIELD_NAME  = 'name';
	const FIELD_PNO   = 'pno';
	const FIELD_EMAIL = 'email';
	const FIELD_PHONE = 'phone';
	const FIELD_FOOD  = 'food';
	const FIELD_NOTE  = 'note';

	const VALIDATION_FIELD_MAPPING = array(
		'email' => self::FIELD_EMAIL,
		'phone' => self::FIELD_PHONE,
		'name'  => self::FIELD_NAME,
		'food'  => self::FIELD_FOOD,
		'note'  => self::FIELD_NOTE,
		'pno'   => self::FIELD_PNO,
	//      'status' => ???
	);

	public function __construct(
		bool $compact,
		bool $read_only,
		bool $required_only,
		bool $show_validation_errors,
		GroupCategoryRules $group_category_rules,
		string $i18n_prefix = 'person.form'
	) {
		$this->person_name_question = new FieldText( Strings::get( "$i18n_prefix.name.label" ), Strings::get( "$i18n_prefix.name.hint" ), $read_only, array(), $compact );
		$this->person_pno_questions = array_combine(
			array_keys( GroupCategoryRules::NIN_OPTIONS ),
			array_map(
				function ( string $nin_rule_name, $nin_rule_config ) use ( $compact, $read_only, $i18n_prefix ) {
					return new FieldText(
						Strings::get( "$i18n_prefix.pno.$nin_rule_name.label" ),
						Strings::get( "$i18n_prefix.pno.$nin_rule_name.hint" ),
						$read_only,
						array(
							'type'        => 'tel',
							'pattern'     => $nin_rule_config['validator'],
							'placeholder' => Strings::get( "$i18n_prefix.pno.$nin_rule_name.placeholder" ),
						),
						$compact
					);
				},
				array_keys( GroupCategoryRules::NIN_OPTIONS ),
				array_values( GroupCategoryRules::NIN_OPTIONS )
			)
		);

		$this->person_email_question  = new FieldEmail( Strings::get( "$i18n_prefix.email.label" ), Strings::get( "$i18n_prefix.email.hint" ), $read_only, $compact );
		$this->person_phone_question  = new FieldPhone( Strings::get( "$i18n_prefix.phone.label" ), Strings::get( "$i18n_prefix.phone.hint" ), $read_only, $compact );
		$this->person_food_questions  = array_combine(
			array_keys( GroupCategoryRules::FOOD_OPTIONS ),
			array_map(
				function ( string $food_rule_name, $food_rule_config ) use ( $compact, $read_only, $i18n_prefix ) {
					switch ( $food_rule_name ) {
						case GroupCategoryRules::FOOD_OPTION_FIXED_OPTIONS:
						case GroupCategoryRules::FOOD_OPTION_FIXED_OPTIONS_AND_CUSTOM:
							return new FieldFood(
								Strings::get( "$i18n_prefix.food.label" ),
								Strings::get( "$i18n_prefix.food.hint" ),
								$read_only,
								$compact,
								array(
									// Source: https://astmaoallergiforbundet.se/information-rad/allergi/matallergi/allergener/
									'Mjölkprotein',
									'Laktos',
									'Jordnötter',
									'Nötter/mandel',
									'Ägg',
									'Vete/spannmål',
									'Celiaki/gluten',
									// 'Senap',
									// 'Selleri',
									// 'Fisk',
									// 'Skaldjur',
									'Soja',
									// 'Lupin',
									// 'Sesamfrö',
									// 'Sulfiter',
								),
								GroupCategoryRules::FOOD_OPTION_FIXED_OPTIONS_AND_CUSTOM === $food_rule_name
							);
						case GroupCategoryRules::FOOD_OPTION_BOOL_REQUIRED:
						case GroupCategoryRules::FOOD_OPTION_BOOL_OPTIONAL:
						case GroupCategoryRules::FOOD_OPTION_BOOL_SKIP:
							return new FieldText(
								Strings::get( "$i18n_prefix.food.label" ),
								Strings::get( "$i18n_prefix.food.hint" ),
								$read_only,
								array(),
								$compact
							);
					}
				},
				array_keys( GroupCategoryRules::FOOD_OPTIONS ),
				array_values( GroupCategoryRules::FOOD_OPTIONS )
			)
		);
		$this->person_note_question   = new FieldText( Strings::get( "$i18n_prefix.note.label" ), Strings::get( "$i18n_prefix.note.hint" ), $read_only, array(), $compact );
		$this->show_validation_errors = $show_validation_errors;
		$this->group_category_rules   = $group_category_rules;
		$this->required_only          = $required_only;
	}


	public function render( Person $person ) {
		$errors = array();
		if ( $this->show_validation_errors ) {
			try {
				$person->validate( $this->group_category_rules );
			} catch ( ValidationException $e ) {
				$field            = self::VALIDATION_FIELD_MAPPING[ $e->getField() ];
				$errors[ $field ] = $e->getMessage();
			}
		}
		try {
			$html_sections = array();
			if ( $this->is_field_visible( $person, GroupCategoryRules::PERSON_PROP_NAME ) ) {
				$html_sections[] = FrontendView::render_field( $this->person_name_question, self::get_field_name( self::FIELD_NAME, $person ), @$errors[ self::FIELD_NAME ], $person->name );
			}
			if ( $this->is_field_visible( $person, GroupCategoryRules::PERSON_PROP_NIN ) ) {
				$rule_value = $this->get_field_value( $person, GroupCategoryRules::PERSON_PROP_NIN );

				$html_sections[] = FrontendView::render_field(
					$this->person_pno_questions[ $rule_value ],
					self::get_field_name( self::FIELD_PNO, $person ),
					@$errors[ self::FIELD_PNO ],
					$person->pno
				);
			}
			if ( $this->is_field_visible( $person, GroupCategoryRules::PERSON_PROP_EMAIL ) ) {
				$html_sections[] = FrontendView::render_field( $this->person_email_question, self::get_field_name( self::FIELD_EMAIL, $person ), @$errors[ self::FIELD_EMAIL ], $person->email );
			}
			if ( $this->is_field_visible( $person, GroupCategoryRules::PERSON_PROP_PHONE ) ) {
				$html_sections[] = FrontendView::render_field( $this->person_phone_question, self::get_field_name( self::FIELD_PHONE, $person ), @$errors[ self::FIELD_PHONE ], $person->phone );
			}
			if ( $this->is_field_visible( $person, GroupCategoryRules::PERSON_PROP_FOOD ) ) {
				$rule_value = $this->get_field_value( $person, GroupCategoryRules::PERSON_PROP_FOOD );

				$html_sections[] = FrontendView::render_field(
					$this->person_food_questions[ $rule_value ],
					self::get_field_name( self::FIELD_FOOD, $person ),
					@$errors[ self::FIELD_FOOD ],
					$person->food
				);
			}
			if ( $this->is_field_visible( $person, GroupCategoryRules::PERSON_PROP_NOTE ) ) {
				$html_sections[] = FrontendView::render_field( $this->person_note_question, self::get_field_name( self::FIELD_NOTE, $person ), @$errors[ self::FIELD_NOTE ], $person->note );
			}

			return join( $html_sections );
		} catch ( Exception $e ) {
			return sprintf( '[error %s]', $e->getMessage() );
		}
	}

	public static function get_field_name( string $field, Person $person ): string {
		return FrontendView::FIELD_PREFIX_PERSON . $field . '__' . ( $person->random_id ?: $person->id ?: '' );
	}

	private function is_field_visible( Person $person, $field ): bool {
		if ( $this->required_only ) {
			return $this->group_category_rules->is_person_field_required( $person->get_type(), $field );
		} else {
			return $this->group_category_rules->is_person_field_enabled( $person->get_type(), $field );
		}
	}

	private function get_field_value( Person $person, $field ) {
		return $this->group_category_rules->get_person_field_value( $person->get_type(), $field );
	}

	public function update_with_posted_values( Person $person ) {
		$is_updated = false;
		// TODO: DRY?
		if ( $this->is_field_visible( $person, GroupCategoryRules::PERSON_PROP_NAME ) ) {
			$new_value = join( '', $this->person_name_question->get_data( self::get_field_name( self::FIELD_NAME, $person ), null, new Group() ) );
			$old_value = $person->name;
			if ( $new_value != $old_value ) {
				$person->name = $new_value;
				$is_updated   = true;
			}
		}
		if ( $this->is_field_visible( $person, GroupCategoryRules::PERSON_PROP_NIN ) ) {
			$rule_value = $this->get_field_value( $person, GroupCategoryRules::PERSON_PROP_NIN );

			$new_value = join( ', ', $this->person_pno_questions[ $rule_value ]->get_data( self::get_field_name( self::FIELD_PNO, $person ), null, new Group() ) );
			$old_value = $person->pno;
			if ( $new_value != $old_value ) {
				$person->pno = $new_value;
				$is_updated  = true;
			}
		}
		if ( $this->is_field_visible( $person, GroupCategoryRules::PERSON_PROP_EMAIL ) ) {
			$new_value = join( '', $this->person_email_question->get_data( self::get_field_name( self::FIELD_EMAIL, $person ), null, new Group() ) );
			$old_value = $person->email;
			if ( $new_value != $old_value ) {
				$person->email = $new_value;
				$is_updated    = true;
			}
		}
		if ( $this->is_field_visible( $person, GroupCategoryRules::PERSON_PROP_PHONE ) ) {
			$new_value = join( '', $this->person_phone_question->get_data( self::get_field_name( self::FIELD_PHONE, $person ), null, new Group() ) );
			$old_value = $person->phone;
			if ( $new_value != $old_value ) {
				$person->phone = $new_value;
				$is_updated    = true;
			}
		}
		if ( $this->is_field_visible( $person, GroupCategoryRules::PERSON_PROP_FOOD ) ) {
			$rule_value = $this->get_field_value( $person, GroupCategoryRules::PERSON_PROP_FOOD );

			$new_value = join( ', ', $this->person_food_questions[ $rule_value ]->get_data( self::get_field_name( self::FIELD_FOOD, $person ), null, new Group() ) );
			$old_value = $person->food;
			if ( $new_value != $old_value ) {
				$person->food = $new_value;
				$is_updated   = true;
			}
		}
		if ( $this->is_field_visible( $person, GroupCategoryRules::PERSON_PROP_NOTE ) ) {
			$new_value = join( '', $this->person_note_question->get_data( self::get_field_name( self::FIELD_NOTE, $person ), null, new Group() ) );
			$old_value = $person->note;
			if ( $new_value != $old_value ) {
				$person->note = $new_value;
				$is_updated   = true;
			}
		}
		return $is_updated;
	}
}
