<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\store\FormDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\QuestionPointsOverrideDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\Frontend;
use tuja\frontend\AbstractCrewMemberView;
use tuja\util\concurrency\LockValuesList;
use tuja\view\FieldNumber;

class PointsOverride extends AbstractCrewMemberView {
	private $question_dao;
	private $points_dao;
	private $form;
	private $question_group_dao;

	const FILTER_DROPDOWN_NAME  = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'filter';
	const FILTER_GROUPS         = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'filter-groups';
	const FILTER_QUESTIONS      = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'filter-questions';
	const QUESTION_FIELD_PREFIX = self::FORM_PREFIX . self::FIELD_NAME_PART_SEP . 'question';

	public function __construct( string $url, string $group_key, int $form_id ) {
		parent::__construct( $url, $group_key, 'Rapportera poäng' );
		$db_form                  = new FormDao();
		$this->form               = $db_form->get( $form_id );
		$this->competition_id     = $this->form->competition_id;
		$this->question_dao       = new QuestionDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->points_dao         = new QuestionPointsOverrideDao();
		$this->category_dao       = new GroupCategoryDao();
	}

	function output() {
		$form = $this->get_form_html();
		include( 'views/points-override.php' );
	}

	public function get_form_html(): string {
		Frontend::use_script( 'jquery' );
		Frontend::use_script( 'tuja-points.js' );

		$html_sections = array();

		$html_sections[] = $this->handle_post();

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
			$questions = $this->get_questions();

			$current_points = $this->points_dao->get_by_competition( $this->competition_id );
			$current_points = array_combine(
				array_map(
					function ( $points ) {
						return self::key( $points->form_question_id, $points->group_id );
					},
					$current_points
				),
				array_values( $current_points )
			);

			foreach ( $questions as $question ) {
				$text            = ( $question->text ? $question->text : $question_group->text ) . ' - ' . $group->name;
				$html_sections[] = sprintf( '<p>%s</p>', $this->render_points_field( $text, $question->score_max, $question->id, $group->id, $current_points ) );
			}

			$html_sections[] = $this->html_optimistic_lock();

			$html_sections[] = $this->html_save_button();
		}

		return join( $html_sections );
	}

	private function render_points_field( $text, $max_score, $question_id, $group_id, $current_points ): string {
		$key        = self::key( $question_id, $group_id );
		$points     = isset( $current_points[ $key ] ) ? $current_points[ $key ]->points : null;
		$field      = new FieldNumber( $text, sprintf( 'Max %d poäng.', $max_score ) ); // TODO: Extract to strings.ini
		$field_name = self::QUESTION_FIELD_PREFIX . self::FIELD_NAME_PART_SEP . $key;

		return $field->render( $field_name, $points );
	}

	public function update_points(): array {
		$errors = array();

		$form_values = array_filter(
			$_POST,
			function ( $key ) {
				return substr( $key, 0, strlen( self::QUESTION_FIELD_PREFIX ) ) === self::QUESTION_FIELD_PREFIX;
			},
			ARRAY_FILTER_USE_KEY
		);

		try {
			$this->check_optimistic_lock();
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
				$question                           = $this->question_dao->get( $question_id );

				if ( $question->score_max < $field_value ) {
					throw new Exception( 'För hög poäng. Max poäng är ' . $question->score_max ); // TODO: Extract to strings.ini
				}

				$this->points_dao->set( $group_id, $question_id, is_numeric( $field_value ) ? intval( $field_value ) : null );
			} catch ( Exception $e ) {
				// TODO: Use the key to display the error message next to the problematic text field.
				$errors[ $field_name ] = $e->getMessage();
			}
		}

		return $errors;
	}

	public function get_filter_field() {
		$render_id = self::FILTER_DROPDOWN_NAME;
		// TODO: Show user if the assigned points will actually be counted or if the use has provided a new answer to the question which in effect nullifies the points assigned here.
		$hint = sprintf( '<small class="tuja-question-hint">%s</small>', 'Kom ihåg att spara innan du byter.' ); // TODO: Extract to strings.ini

		return sprintf(
			'<div class="tuja-field"><label for="%s">%s%s</label>%s</div>',
			$render_id,
			'Vad vill du rapportera för?', // TODO: Extract to strings.ini
			$hint,
			$this->render_filter_dropdown()
		);
	}

	public function render_filter_dropdown() {
		$question_options = join(
			array_map(
				function ( QuestionGroup $qg ) {
					$value = $qg->id;
					$label = $qg->text;
					return sprintf(
						'<option value="%s"%s>%s</option>',
						$value,
						isset( $_GET['q'] ) && $value == $_GET['q'] ? ' selected="selected"' : '',
						htmlspecialchars( $label )
					);
				},
				$this->question_group_dao->get_all_in_form( $this->form->id )
			)
		);

		$group_options = join(
			array_map(
				function ( Group $group ) {
					$value = $group->id;
					$label = $group->name;
					return sprintf(
						'<option value="%s" %s>%s</option>',
						$value,
						isset( $_GET['g'] ) && $value == $_GET['g'] ? ' selected="selected"' : '',
						htmlspecialchars( $label )
					);
				},
				$this->get_participant_groups()
			)
		);

		ob_start();
		// TODO: Extract to strings.ini
		?>
		<select id="<?php echo self::FILTER_QUESTIONS; ?>"
				name="<?php echo self::FILTER_QUESTIONS; ?>"
				class="tuja-fieldchoices tuja-fieldchoices-longlist">
			<option value="">Välj kontroll</option>
			<?php echo $question_options; ?>
		</select>
		<select id="<?php echo self::FILTER_GROUPS; ?>"
				name="<?php echo self::FILTER_GROUPS; ?>"
				class="tuja-fieldchoices tuja-fieldchoices-longlist">
			<option value="">Välj grupp</option>
			<?php echo $group_options; ?>
		</select>
		<?php
		return ob_get_clean();
	}

	function get_questions(): array {
		return $this->question_dao->get_all_in_group( (int) @$_GET['q'] ?? 0 );
	}

	private static function key( int $question_id, int $group_id ): string {
		return $question_id . self::FIELD_NAME_PART_SEP . $group_id;
	}

	function get_optimistic_lock(): LockValuesList {
		$lock = new LockValuesList();

		$questions                     = $this->get_questions();
		$selected_participant_group_id = ( (int) @$_GET['g'] ) ?? 0;

		$keys = array_map(
			function ( AbstractQuestion $question ) use ( $selected_participant_group_id ) {
				return self::key( $question->id, $selected_participant_group_id );
			},
			$questions
		);

		$current_points = $this->points_dao->get_by_competition( $this->competition_id );
		$points_by_key  = array_combine(
			array_map(
				function ( $points ) {
					return self::key( $points->form_question_id, $points->group_id );
				},
				$current_points
			),
			array_values( $current_points )
		);

		array_walk(
			$keys,
			function ( string $key ) use ( $points_by_key, $lock ) {
				if ( isset( $points_by_key[ $key ] ) && null !== $points_by_key[ $key ]->created ) {
					$lock->add_value( $key, $points_by_key[ $key ]->created->getTimestamp() );
				} else {
					$lock->add_value( $key, 0 );
				}
			},
			0
		);

		return $lock;
	}
}
