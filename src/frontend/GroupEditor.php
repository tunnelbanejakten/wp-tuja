<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\ValidationException;
use tuja\Frontend;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\util\rules\RuleEvaluationException;
use tuja\util\Strings;
use tuja\view\FieldChoices;
use tuja\view\FieldText;

class GroupEditor extends AbstractGroupView {
	private $group_key;

	private $enable_group_category_selection = true;
	private $read_only;

	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Ändra %s' );
		$this->group_key = $group_key;
	}

	function output() {

		Frontend::use_script( 'jquery' );
		Frontend::use_script( 'tuja-edit-group.js' );

		$group = $this->get_group();

		$this->check_group_not_on_waiting_list( $group );

		$errors = [];

		if ( @$_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE ) {
			try {
				$errors = $this->update_group( $group );
				if ( empty( $errors ) ) {
					printf( '<p class="tuja-message tuja-message-success">%s</p>', 'Ändringarna har sparats. Tack.' );
				}
			} catch ( RuleEvaluationException $e ) {
				$errors = array( '__' => $e->getMessage() );
			}
		}

		$errors_overall = isset( $errors['__'] ) ? sprintf( '<p class="tuja-message tuja-message-error">%s</p>', $errors['__'] ) : '';

		$form          = $this->get_form_group_html( $errors, true );
		$submit_button = $this->get_form_save_button_html();
		$home_link     = GroupHomeInitiator::link( $group );
		include( 'views/group-editor.php' );
	}

	// Move to AbstractGroupView
	function is_read_only(): bool {
		if ( ! isset( $this->read_only ) ) {
			$this->read_only = ! $this->is_edit_allowed( $this->get_group() );
		}

		return $this->read_only;
	}

	private function get_form_group_html( array $errors, $show_note ) {
		$html_sections = [];

		$group = $this->get_group();

		$group_name_question = new FieldText( 'Vad heter ert lag?', null, $this->is_read_only() );
		$html_sections[]     = $this->render_field( $group_name_question, self::FIELD_GROUP_NAME, @$errors['name'], $group->name );

		$group_city_question = new FieldText( 'Vilken ort kommer ni från?', null, $this->is_read_only() );
		$html_sections[]     = $this->render_field( $group_city_question, self::FIELD_GROUP_CITY, @$errors['city'], $group->city );

		if ( $this->enable_group_category_selection ) {
			$categories = $this->get_categories( $group->competition_id );

			$group_category = $group->get_category();

			$current_group_category_name = current( array_filter( $categories, function ( $category ) use ( $group_category ) {
				return isset( $group_category ) && $group_category->id == $category->id;
			} ) )->name;

			$group_category_options = array_map( function ( $category ) {
				return $category->name;
			}, $categories );

			switch ( count( $group_category_options ) ) {
				case 0:
					break;
				case 1:
					$html_sections[] = sprintf( '<input type="hidden" name="%s" value="%s">', self::FIELD_GROUP_AGE, htmlentities( $group_category_options[0] ) );
					break;
				default:
					$group_category_question = new FieldChoices(
						'Vilken klass tävlar ni i?',
						Strings::get('group.form.age.hint'),
						false,
						$group_category_options,
						false );
					$html_sections[]         = $this->render_field( $group_category_question, self::FIELD_GROUP_AGE, @$errors['age'], $current_group_category_name );
					break;
			}
		}

		if ( $show_note ) {
			$person_name_question = new FieldText( 'Något annat vi borde känna till?', Strings::get( 'group.form.note.hint' ) );
			$html_sections[]      = $this->render_field( $person_name_question, self::FIELD_GROUP_NOTE, @$errors['note'], $group->note );
		}

		return join( $html_sections );
	}

	private function get_form_save_button_html() {
		$html_sections = [];

		if ( ! $this->is_read_only() ) {
			$html_sections[] = sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="%s" id="tuja_save_button">%s</button></div>',
				self::ACTION_BUTTON_NAME,
				self::ACTION_NAME_SAVE,
				'Spara' );
		} else {
			// TODO: Should other error messages also contain email link?
			$html_sections[] = sprintf( '<p class="tuja-message tuja-message-error">%s</p>',
				sprintf( 'Du kan inte längre ändra er anmälan. Kontakta <a href="mailto:%s">%s</a> om du behöver ändra något.',
					get_bloginfo( 'admin_email' ),
					get_bloginfo( 'admin_email' ) ) );
		}

		return join( $html_sections );
	}

	private function update_group( Group $group ) {
		// INIT
		$validation_errors = array();
		$overall_success   = true;
		$group_id          = $group->id;
		$competition       = $this->competition_dao->get( $group->competition_id );
		$category          = $this->enable_group_category_selection ? $this->get_posted_category( $competition->id ) : null;

		// DETERMINE REQUESTED CHANGES
		$posted_values         = [];
		$posted_values['name'] = $_POST[ self::FIELD_GROUP_NAME ];
		$posted_values['city'] = $_POST[ self::FIELD_GROUP_CITY ];
		$posted_values['note'] = $_POST[ self::FIELD_GROUP_NOTE ];
		if ( isset( $category ) ) {
			$posted_values['category_id'] = $category->id;
		}
		$is_group_property_updated = false;
		foreach ( $posted_values as $prop => $new_value ) {
			if ( $group->{$prop} != $new_value ) {
				$group->{$prop} = $new_value;

				$is_group_property_updated = true;
			}
		}

		if ( ! $this->is_edit_allowed( $group ) ) {
			throw new RuleEvaluationException( 'Det går inte att ändra anmälan nu' );
		}

		// SAVE CHANGES
		if ( $is_group_property_updated ) {
			try {
				$affected_rows = $this->group_dao->update( $group );
				if ( $affected_rows === false ) {
					$overall_success = false;
				}
				$this->group_dao->run_registration_rules( $group );
			} catch ( ValidationException $e ) {
				$validation_errors[ $e->getField() ] = $e->getMessage();
				$overall_success                     = false;
			} catch ( Exception $e ) {
				$overall_success = false;
			}
		}

		if ( ! $overall_success ) {
			$validation_errors['__'] = 'Alla ändringar kunde inte sparas.';
		}

		return $validation_errors;
	}
}