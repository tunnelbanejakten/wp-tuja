<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\util\rules\RuleEvaluationException;
use tuja\view\EditGroupShortcode;
use tuja\view\FieldChoices;
use tuja\view\FieldEmail;
use tuja\view\FieldPhone;
use tuja\view\FieldPno;
use tuja\view\FieldText;

class GroupEditor extends AbstractGroupView {
	private $group_key;

	const ACTION_NAME_DELETE_PERSON_PREFIX = 'delete_person__';

	private $enable_group_category_selection = true;
	private $read_only;

	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Ändra %s' );
		$this->group_key = $group_key;
	}

	function output() {

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'tuja-editgroup-script' ); // Needed?

		try {
			$group      = $this->get_group();
			$form_group = $this->get_form_group_html();
			$errors     = [];

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

			$form_save_button   = $this->get_form_save_button_html();
			$home_link          = GroupHomeInitiator::link( $group );
			include( 'views/group-editor.php' );
		} catch ( Exception $e ) {
			printf( '<p class="tuja-message tuja-message-error">%s</p>', $e->getMessage() );
		}
	}

	// Move to AbstractGroupView
	function is_read_only(): bool {
		if ( ! isset( $this->read_only ) ) {
			$this->read_only = ! $this->is_edit_allowed( $this->get_group() );
		}

		return $this->read_only;
	}

	private function get_form_group_html() {
		$html_sections = [];

		$group = $this->get_group();

		$group_name_question = new FieldText( 'Vad heter ert lag?', null, $this->is_read_only() );
		$html_sections[]     = $this->render_field( $group_name_question, self::FIELD_GROUP_NAME, @$errors['name'], $group->name );

		if ( $this->enable_group_category_selection ) {
			$categories = $this->get_categories( $group->competition_id );

			$group_category = $group->get_derived_group_category();

			$current_group_category_name = reset( array_filter( $categories, function ( $category ) use ( $group_category ) {
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
						'Välj den som de flesta av deltagarna tillhör.',
						false,
						$group_category_options,
						false );
					$html_sections[]         = $this->render_field( $group_category_question, self::FIELD_GROUP_AGE, @$errors['age'], $current_group_category_name );
					break;
			}
		}


		return join( $html_sections );
	}

	private function get_form_save_button_html() {
		$html_sections = [];

		if ( ! $this->is_read_only() ) {
			$html_sections[] = sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="%s">%s</button></div>',
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

	public function _____get_form(): String {
		$group_key = $this->group_key;

		if ( isset( $group_key ) ) {
			$group = $this->group_dao->get_by_key( $group_key );
			if ( $group === false ) {
				return sprintf( '<p class="tuja-message tuja-message-error">%s</p>', 'Oj, vi vet inte vilket lag du är med i.' );
			}

			$is_read_only = ! $this->is_edit_allowed( $group );
			$errors       = array();

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

			return $this->render_update_form( $group, $errors, $is_read_only );
		} else {
			return sprintf( '<p class="tuja-message tuja-message-error">%s</p>', 'Oj, vi vet inte vilket lag du är med i.' );
		}
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