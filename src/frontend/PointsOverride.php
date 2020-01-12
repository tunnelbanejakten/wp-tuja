<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\store\FormDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\PointsDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\view\FieldNumber;

class PointsOverride extends AbstractGroupView {
	private $competition_id;
	private $question_dao;
	private $points_dao;
	private $participant_groups;
	private $category_dao;
	private $form;
	private $question_group_dao;

	const FIELD_NAME_PART_SEP = '__';
	const FORM_PREFIX = 'tuja_pointsshortcode';
	const OPTIMISTIC_LOCK_FIELD_NAME = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'optimistic_lock';
	const ACTION_FIELD_NAME = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'action';
	const FILTER_DROPDOWN_NAME = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'filter';
	const FILTER_GROUPS = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'filter-groups';
	const FILTER_QUESTIONS = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'filter-questions';
	const QUESTION_FIELD_PREFIX = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'question';

	public function __construct( string $url, string $group_key, int $form_id ) {
		parent::__construct( $url, $group_key, 'Rapportera poäng' );
		$db_form                  = new FormDao();
		$this->form               = $db_form->get( $form_id );
		$this->competition_id     = $this->form->competition_id;
		$this->question_dao       = new QuestionDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->points_dao         = new PointsDao();
		$this->category_dao       = new GroupCategoryDao();
	}

	function output() {
		$form = $this->get_form_html();
		include( 'views/points-override.php' );
	}

	public function get_form_html(): String {
		// Validate ID
		$crew_group = $this->get_group();

		// Validate group category
		$group_category = $crew_group->get_category();
		if ( isset( $group_category ) && ! $group_category->get_rule_set()->is_crew() ) {
			throw new Exception( 'Bara funktionärer får använda detta formulär.' );
		}

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'tuja-points-script' );

		$html_sections = [];

		// Save points
		if ( isset( $_POST[ self::ACTION_FIELD_NAME ] ) && $_POST[ self::ACTION_FIELD_NAME ] == 'update' ) {
			$errors = $this->update_points();
			if ( empty( $errors ) ) {
				$html_sections[] = sprintf( '<p class="tuja-message tuja-message-success">%s</p>', 'Poängen har sparats.' );
			} else {
				$html_sections[] = sprintf( '<p class="tuja-message tuja-message-error">%s</p>', join( '. ', $errors ) );
			}
		}

		$html_sections[] = sprintf( '<p>%s</p>', $this->get_filter_field() );

		$group = false;
		if ( isset( $_GET['g'] ) ) {
			$group = $this->group_dao->get( (int) $_GET['g'] );
		}

		$question_group = false;
		if ( isset( $_GET['q'] ) ) {
			$question_group = $this->question_group_dao->get( (int) $_GET['q'] );
		}

		// If a group and question group has been selected, display the questions with current points and a save button
		if ( $group && $question_group ) {
			$questions = $this->question_dao->get_all_in_group( $question_group->id );

			$current_points = $this->points_dao->get_by_competition( $this->competition_id );
			$current_points = array_combine(
				array_map( function ( $points ) {
					return $points->form_question_id . self::FIELD_NAME_PART_SEP . $points->group_id;
				}, $current_points ),
				array_values( $current_points )
			);

			foreach ( $questions as $question ) {
				$text            = ( $question->text ? $question->text : $question_group->text ) . ' - ' . $group->name;
				$html_sections[] = sprintf( '<p>%s</p>', $this->render_points_field( $text, $question->score_max, $question->id, $group->id, $current_points ) );
			}

			$optimistic_lock_value = $this->get_optimistic_lock_value( $this->get_keys( $group->id, $questions ) );

			$html_sections[] = sprintf( '<input type="hidden" name="%s" value="%s">', self::OPTIMISTIC_LOCK_FIELD_NAME, $optimistic_lock_value );

			$html_sections[] = sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="update">Spara</button></div>', self::ACTION_FIELD_NAME );
		}

		return join( $html_sections );
	}

	private function render_points_field( $text, $max_score, $question_id, $group_id, $current_points ): string {
		$key        = $question_id . self::FIELD_NAME_PART_SEP . $group_id;
		$points     = isset( $current_points[ $key ] ) ? $current_points[ $key ]->points : null;
		$field      = new FieldNumber( $text, sprintf( 'Max %d poäng.', $max_score ) );
		$field_name = self::QUESTION_FIELD_PREFIX . self::FIELD_NAME_PART_SEP . $question_id . self::FIELD_NAME_PART_SEP . $group_id;

		return $field->render( $field_name, $points );
	}

	public function update_points(): array {
		$errors = array();

		$form_values = array_filter( $_POST, function ( $key ) {
			return substr( $key, 0, strlen( self::QUESTION_FIELD_PREFIX ) ) === self::QUESTION_FIELD_PREFIX;
		}, ARRAY_FILTER_USE_KEY );

		try {
			$this->check_optimistic_lock( $form_values );
		} catch ( Exception $e ) {
			// We do not want to present the previously inputted values in case we notice that another user has assigned score to the same questions.
			// The responses inputted for the previously selected group are not relevant anymore (they are, in fact, probably incorrect).
			foreach ( $form_values as $field_name => $field_value ) {
				unset( $_POST[ $field_name ] );
			}

			return array( $e->getMessage() );
		}

		foreach ( $form_values as $field_name => $field_value ) {
			try {
				list( , , $question_id, $group_id ) = explode( self::FIELD_NAME_PART_SEP, $field_name );
				$question = $this->question_dao->get( $question_id );

				if ( $question->score_max < $field_value ) {
					throw new Exception( 'För hög poäng. Max poäng är ' . $question->score_max );
				}

				$this->points_dao->set( $group_id, $question_id, is_numeric( $field_value ) ? intval( $field_value ) : null );
			} catch ( Exception $e ) {
				// TODO: Use the key to display the error message next to the problematic text field.
				$errors[ $field_name ] = $e->getMessage();
			}
		}

		return $errors;
	}

	private function get_keys( $group_id, $questions ): array {
		$keys = [];
		foreach ( $questions as $question ) {
			$keys[] = new PointsOverridePointsKey( $group_id, $question->id );
		}

		return $keys;
	}

	public function get_filter_field() {
		$render_id = self::FILTER_DROPDOWN_NAME;
		// TODO: Show user if the assigned points will actually be counted or if the use has provided a new answer to the question which in effect nullifies the points assigned here.
		$hint = sprintf( '<small class="tuja-question-hint">%s</small>', 'Kom ihåg att spara innan du byter.' );

		return sprintf( '<div class="tuja-field"><label for="%s">%s%s</label>%s</div>',
			$render_id,
			'Vad vill du rapportera för?',
			$hint,
			$this->render_filter_dropdown()
		);
	}

	public function render_filter_dropdown() {
		$questions = $this->question_group_dao->get_all_in_form( $this->form->id );
		$groups    = $this->get_participant_groups();

		$question_option_values = array_map( function ( $q ) {
			return $q->id;
		}, $questions );
		$question_option_labels = array_map( function ( $q ) {
			return $q->text;
		}, $questions );
		$question_options       = join( array_map( function ( $value, $label ) {
			return sprintf( '<option value="%s"%s>%s</option>',
				$value,
				isset( $_GET['q'] ) && $value == $_GET['q'] ? ' selected="selected"' : '',
				htmlspecialchars( $label ) );
		}, $question_option_values, $question_option_labels ) );

		$group_option_values = array_map( function ( $group ) {
			return $group->id;
		}, $groups );
		$group_option_labels = array_map( function ( $group ) {
			return $group->name;
		}, $groups );
		$group_options       = join( array_map( function ( $value, $label ) {
			return sprintf( '<option value="%s" %s>%s</option>',
				$value,
				isset( $_GET['g'] ) && $value == $_GET['g'] ? ' selected="selected"' : '',
				htmlspecialchars( $label ) );
		}, $group_option_values, $group_option_labels ) );

		ob_start();
		?>
        <select id="<?= self::FILTER_QUESTIONS; ?>" name="<?= self::FILTER_QUESTIONS; ?>"
                class="tuja-fieldchoices tuja-fieldchoices-longlist">
            <option value="">Välj kontroll</option>
			<?= $question_options; ?>
        </select>
        <select id="<?= self::FILTER_GROUPS; ?>" name="<?= self::FILTER_GROUPS; ?>"
                class="tuja-fieldchoices tuja-fieldchoices-longlist">
            <option value="">Välj grupp</option>
			<?= $group_options; ?>
        </select>
		<?php
		return ob_get_clean();
	}

	private function get_participant_groups(): array {
		if ( ! isset( $this->participant_groups ) ) {
			// TODO: DRY... Very similar code in Form.php
			$categories             = $this->category_dao->get_all_in_competition( $this->competition_id );
			$participant_categories = array_filter( $categories, function ( $category ) {
				return ! $category->get_rule_set()->is_crew();
			} );
			$ids                    = array_map( function ( $category ) {
				return $category->id;
			}, $participant_categories );

			$competition_groups       = $this->group_dao->get_all_in_competition( $this->competition_id );
			$this->participant_groups = array_filter( $competition_groups, function ( Group $group ) use ( $ids ) {
				$group_category = $group->get_category();

				return isset( $group_category ) && in_array( $group_category->id, $ids );
			} );
		}

		return $this->participant_groups;
	}

	private function get_optimistic_lock_value( array $keys ) {
		$current_points = $this->points_dao->get_by_competition( $this->competition_id );
		$points_by_key  = array_combine(
			array_map( function ( $points ) {
				return $points->form_question_id . self::FIELD_NAME_PART_SEP . $points->group_id;
			}, $current_points ),
			array_values( $current_points ) );

		$current_optimistic_lock_value = array_reduce( $keys, function ( $carry, PointsOverridePointsKey $key ) use ( $points_by_key ) {
			$temp_key = $key->question_id . self::FIELD_NAME_PART_SEP . $key->group_id;

			$response_timestamp = 0;
			if ( isset( $points_by_key[ $temp_key ] ) && $points_by_key[ $temp_key ]->created != null ) {
				$response_timestamp = $points_by_key[ $temp_key ]->created->getTimestamp();
			}

			return max( $carry, $response_timestamp );
		}, 0 );

		return $current_optimistic_lock_value;
	}

	private function check_optimistic_lock( $form_values ) {
		$keys = array_map( function ( $field_name ) {
			list( , , $question_id, $group_id ) = explode( self::FIELD_NAME_PART_SEP, $field_name );

			return new PointsOverridePointsKey( $group_id, $question_id );
		}, array_keys( $form_values ) );

		$current_optimistic_lock_value = $this->get_optimistic_lock_value( $keys );

		if ( $current_optimistic_lock_value != $_POST[ self::OPTIMISTIC_LOCK_FIELD_NAME ] ) {
			throw new Exception( '' .
			                     'Någon annan har hunnit rapportera in andra poäng för dessa frågor/lag sedan du ' .
			                     'laddade den här sidan. För att undvika att du av misstag skriver över andra ' .
			                     'funktionärers poäng så sparades inte poängen du angav. De senast inrapporterade ' .
			                     'poängen visas istället för de du rapporterade in.' );
		}
	}
}