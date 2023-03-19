<?php

namespace tuja\admin;

use Exception;
use tuja\data\model\question\ImagesQuestion;
use tuja\data\model\question\NumberQuestion;
use tuja\data\model\question\OptionsQuestion;
use tuja\data\model\question\TextQuestion;
use tuja\data\store\QuestionDao;
use tuja\data\store\CompetitionDao;
use tuja\util\QuestionNameGenerator;
use tuja\util\RouterInterface;

class FormQuestion extends FormQuestionGroup implements RouterInterface {
	const FORM_FIELD_NAME_PREFIX    = 'tuja-question';
	const ACTION_NAME_DELETE_PREFIX = 'question_delete__';
	const ACTION_NAME_UPDATE_PREFIX = 'question_update__';

	protected $question_dao;
	protected $question;

	public function __construct() {
		parent::__construct();
		$this->question_dao = new QuestionDao();

		if ( isset( $_GET['tuja_question'] ) ) {
			$this->question = self::get_question($_GET['tuja_question'], $this->question_dao);

			if (!$this->question->form_id) {
				$this->question->form_id = $this->form->id;
			}

			if (!$this->question->question_group_id) {
				$this->question->question_group_id = $this->question_group->id;
			}
		}

		$this->assert_set( 'Could not find question', $this->question );
		$this->assert_same( 'Question needs to belong to group', $this->question_group->id, $this->question->question_group_id );
	}

	private static function get_question($id, $question_dao) {
		if (empty($id)) {
			throw new Exception( 'Missing question parameter' );
		}
		
		if (is_numeric($id)) {
			return $question_dao->get( $id );
		}

		switch ( $id ) {
			case self::ACTION_NAME_CREATE_CHOICES:
				return new OptionsQuestion();
			case self::ACTION_NAME_CREATE_IMAGES:
				return new ImagesQuestion();
			case self::ACTION_NAME_CREATE_TEXT:
				return new TextQuestion();
			case self::ACTION_NAME_CREATE_NUMBER:
				return new NumberQuestion();
			default:
				throw new Exception( 'Unsupported action' );
		}
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		$question_current = null;
		$question_links   = array();
		$questions        = $this->question_dao->get_all_in_group( intval( $_GET['tuja_question_group'] ) );

		foreach ( $questions as $question ) {
			$active = $question->id === $this->question->id;

			if ( $active ) {
				$question_current = $question->name ?? $question->id;
			}

			$link = add_query_arg([
				'tuja_view'     => 'FormQuestion',
				'tuja_question' => $question->id,
			]);
			$question_links[] = BreadcrumbsMenu::item( $question->name ?? $question->id, $link, $active );
		}

		$menu->add(
			BreadcrumbsMenu::item( $question_current ),
			...$question_links,
		);

		return $menu;
	}

	public function handle_post() {
		global $wpdb;

		if ( ! isset( $_POST['tuja_action'] ) ) {
			return;
		}

		$id = self::FORM_FIELD_NAME_PREFIX . '__' . $this->question->id;

		if ( strpos($_POST['tuja_action'], self::ACTION_NAME_CREATE_PREFIX) !== false ) {
			$success = false;

			if(empty($id)) {
				$this->question->set_properties_from_array( $_POST );
			} else {
				$this->question->set_properties_from_json_string( stripslashes( $_POST[ $id ] ) );
			}
			
			try {
				$new_id = $this->question_dao->create( $this->question );
			} catch ( Exception $e ) {
				// Do nothing
			}

			if (empty($new_id)) {
				AdminUtils::printError( 'Kunde inte skapa fråga.' );
			}
		}

		elseif ( strpos($_POST['tuja_action'], self::ACTION_NAME_UPDATE_PREFIX) !== false ) {
			$wpdb->show_errors();

			$success = true;

			if ( isset( $_POST[ $id ] ) ) {
				if(empty($_POST[ $id ])) {
					$this->question->set_properties_from_array( $_POST );
				} else {
					$this->question->set_properties_from_json_string( stripslashes( $_POST[ $id ] ) );
				}

				try {
					$affected_rows = $this->question_dao->update( $this->question );
					$success       = $success && $affected_rows !== false;
				} catch ( Exception $e ) {
					$success = false;
				}
			}

			$success ? AdminUtils::printSuccess( 'Uppdaterat!' ) : AdminUtils::printError( 'Kunde inte uppdatera fråga.' );
		}
		
		elseif ( substr( $_POST['tuja_action'], 0, strlen( self::ACTION_NAME_DELETE_PREFIX ) ) == self::ACTION_NAME_DELETE_PREFIX ) {
			$question_id_to_delete = substr( $_POST['tuja_action'], strlen( self::ACTION_NAME_DELETE_PREFIX ) );
			$affected_rows         = $this->question_dao->delete( $question_id_to_delete );
			$success               = $affected_rows !== false && $affected_rows === 1;

			if ( $success ) {
				AdminUtils::printSuccess( 'Fråga borttagen!' );
			} else {
				AdminUtils::printError( 'Kunde inte ta bort fråga.' );
				if ( $error = $wpdb->last_error ) {
					AdminUtils::printError( $error );
				}
			}
		}

		QuestionNameGenerator::update_competition_questions( $this->form->competition_id );

		if (!empty($new_id)) {
			wp_redirect(add_query_arg(['tuja_question' => $new_id, 'tuja_view' => 'FormQuestion']));
			exit;
		}
	}

	public function output() {
		$db_competition = new CompetitionDao();
		$competition    = $db_competition->get( $this->form->competition_id );
		$question       = $this->question;
		$question_class_short = substr( get_class( $question ), strrpos( get_class( $question ), '\\' ) + 1 );
		$json       = $question->get_editable_properties_json( $question );
		$field_name = self::FORM_FIELD_NAME_PREFIX . '__' . $question->id;
		$options_schema = $question->json_schema();
		$preview_url    = $this->get_preview_url();

		include( 'views/form-question.php' );
	}
}
