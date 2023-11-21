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
	const ACTION_NAME_DELETE = 'question_delete';
	const ACTION_NAME_UPDATE = 'question_update';

	protected $question_dao;
	protected $question;

	public function __construct() {
		parent::__construct();
		$this->question_dao = new QuestionDao();

		if ( isset( $_GET['tuja_question'] ) ) {
			$this->question = self::get_question($_GET['tuja_question'], $this->question_dao);

			if ( !isset( $this->question->form_id ) || !$this->question->form_id) {
				$this->question->form_id = $this->form->id;
			}

			if ( !isset( $this->question->question_group_id ) || !$this->question->question_group_id) {
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

		if ( strpos($_POST['tuja_action'], self::ACTION_NAME_CREATE_PREFIX) !== false ) {
			$success = false;

			$this->question->set_properties_from_array( $_POST );
			
			try {
				$new_id = $this->question_dao->create( $this->question );
			} catch ( Exception $e ) {
				// Do nothing
			}

			if (empty($new_id)) {
				AdminUtils::printError( 'Kunde inte skapa fråga.' );
			}
		}

		elseif ( $_POST['tuja_action'] === self::ACTION_NAME_UPDATE ) {
			$wpdb->show_errors();

			$success = true;

			$this->question->set_properties_from_array( $_POST );

			try {
				$affected_rows = $this->question_dao->update( $this->question );
				$success       = $success && $affected_rows !== false;
			} catch ( Exception $e ) {
				$success = false;
			}

			$success ? AdminUtils::printSuccess( 'Uppdaterat!' ) : AdminUtils::printError( 'Kunde inte uppdatera fråga.' );
		}
		
		elseif ( $_POST['tuja_action'] === self::ACTION_NAME_DELETE ) {
			$affected_rows         = $this->question_dao->delete( $this->question->id );
			$success               = $affected_rows !== false && $affected_rows === 1;

			if ( $success ) {
				$back_url = add_query_arg(
					array(
						'tuja_competition'    => $this->competition->id,
						'tuja_form'           => $this->question->form_id,
						'tuja_question_group' => $this->question->question_group_id,
						'tuja_view'           => 'FormQuestionGroup',
					)
				);

				wp_redirect( $back_url );
				exit;
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
		$preview_url    = $this->get_preview_url();

		include( 'views/form-question.php' );
	}
}
