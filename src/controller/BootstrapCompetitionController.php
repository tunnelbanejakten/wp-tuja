<?php
namespace tuja\controller;

use Exception;
use tuja\data\model\Competition;
use tuja\data\model\Form;
use tuja\data\model\Group;
use tuja\data\model\GroupCategory;
use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\question\ImagesQuestion;
use tuja\data\model\question\NumberQuestion;
use tuja\data\model\question\OptionsQuestion;
use tuja\data\model\question\TextQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\store\CompetitionDao;
use tuja\data\store\FormDao;
use tuja\data\store\GroupCategoryDao;
use tuja\data\store\GroupDao;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;
use tuja\util\rules\CrewMembersRuleSet;
use tuja\util\rules\GroupCategoryRules;
use tuja\util\rules\OlderParticipantsRuleSet;
use tuja\util\rules\YoungParticipantsRuleSet;

class BootstrapCompetitionController {
	private $competition_dao;

	function __construct() {
		$this->competition_dao    = new CompetitionDao();
		$this->category_dao       = new GroupCategoryDao();
		$this->group_dao          = new GroupDao();
		$this->form_dao           = new FormDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->question_dao       = new QuestionDao();
	}

	function bootstrap_competition( BootstrapCompetitionParams $params ) {
		$competition = $this->create_competition( $params->name );
		if ( $params->create_default_group_categories ) {
			$this->create_group_categories( $competition, $params->create_default_crew_groups );
		}
		if ( $params->create_sample_form ) {
			$form           = $this->create_form( $competition );
			$question_group = $this->create_question_group( $form );

			$this->create_text_question( $question_group );
			$this->create_number_question( $question_group );
			$this->create_images_question( $question_group );
			$this->create_options_question( $question_group );
		}
	}

	private function create_competition( string $name ) {
		$competition       = new Competition();
		$competition->name = $name;

		$competition_id = $this->competition_dao->create( $competition );
		if ( $competition_id === false ) {
			throw new Exception( 'Could not create competition.' );
		}
		$competition = $this->competition_dao->get( $competition_id );
		return $competition;
	}

	private function create_group_categories( Competition $competition, bool $create_default_crew_groups ) {
		$rule_sets = array(
			'Crew Members'       => new CrewMembersRuleSet(),
			'Young Participants' => new YoungParticipantsRuleSet(),
			'Older Participants' => new OlderParticipantsRuleSet(),
		);
		foreach ( $rule_sets as $name => $rule_set ) {
			$props                 = new GroupCategory();
			$props->competition_id = $competition->id;
			$props->name           = $name;
			$props->set_rules( GroupCategoryRules::from_rule_set( $rule_set, $competition ) );

			$category_id = $this->category_dao->create( $props );
			if ( $category_id !== false && $create_default_crew_groups && $rule_set->is_crew() ) {
				$group_props                     = new Group();
				$group_props->competition_id     = $competition->id;
				$group_props->map_id             = null;
				$group_props->name               = $name;
				$group_props->note               = null;
				$group_props->city               = null;
				$group_props->is_always_editable = false;
				$group_props->category_id        = $category_id;
				$group_props->set_status( Group::DEFAULT_STATUS );

				$group_id = $this->group_dao->create( $group_props );
			}
		}
	}

	private function create_form( Competition $competition ) : Form {
		$props                                     = new Form();
		$props->name                               = 'Ett formulär';
		$props->competition_id                     = $competition->id;
		$props->allow_multiple_responses_per_group = false;
		$props->submit_response_start              = null;
		$props->submit_response_end                = null;

		$form_id = $this->form_dao->create( $props );
		if ( $form_id === false ) {
			throw new Exception( 'Could not create form.' );
		}
		$form = $this->form_dao->get( $form_id );
		return $form;
	}

	private function create_question_group( Form $form ) : QuestionGroup {
		$question_group_props                   = new QuestionGroup();
		$question_group_props->form_id          = $form->id;
		$question_group_props->text             = 'En grupp frågor';
		$question_group_props->text_description = null;
		$question_group_props->sort_order       = 0;
		$question_group_props->score_max        = null;
		$question_group_props->question_filter  = QuestionGroup::QUESTION_FILTER_ALL;

		$question_group_id = $this->question_group_dao->create( $question_group_props );
		if ( $question_group_id === false ) {
			throw new Exception( 'Could not create question group.' );
		}
		$question_group = $this->question_group_dao->get( $question_group_id );
		return $question_group;
	}

	private function create_text_question( QuestionGroup $question_group ): AbstractQuestion {
		$question_props = new TextQuestion(
			'Vad heter den utomjordiske superhjälten som jobbar på en tidningsredaktion?',
			'En ledtråd',
			0,
			$question_group->id,
			0,
			-1,
			10,
			TextQuestion::GRADING_TYPE_ONE_OF,
			true,
			array( 'stålmannen', 'superman', 'clark kent' ),
			array( 'spindelmannen', 'spider man', 'peter parker' )
		);
		return $this->create_question( $question_group, $question_props );
	}

	private function create_number_question( QuestionGroup $question_group ): AbstractQuestion {
		$question_props = new NumberQuestion(
			'Vad är svaret på den yttersta frågan om livet, universum och allting?',
			'En ledtråd',
			0,
			$question_group->id,
			0,
			-1,
			10,
			42
		);
		return $this->create_question( $question_group, $question_props );
	}

	private function create_images_question( QuestionGroup $question_group ): AbstractQuestion {
		$question_props = new ImagesQuestion(
			'Ta en bild på något som får dig att le',
			'En ledtråd',
			0,
			$question_group->id,
			0,
			-1,
			10,
			ImagesQuestion::DEFAULT_FILE_COUNT_LIMIT
		);
		return $this->create_question( $question_group, $question_props );
	}

	private function create_options_question( QuestionGroup $question_group ): AbstractQuestion {
		$question_props = new OptionsQuestion(
			'Vilka av de här städerna är europeiska huvudstäder?',
			'En ledtråd',
			0,
			$question_group->id,
			0,
			-1,
			10,
			OptionsQuestion::GRADING_TYPE_ALL_OF,
			true,
			array( 'stockholm', 'helsingfors', 'berlin', 'paris', 'rom' ),
			array( 'stockholm', 'helsingfors', 'berlin', 'paris', 'rom', 'göteborg', 'bonn', 'milano', 'barcelona' ),
			false
		);
		return $this->create_question( $question_group, $question_props );
	}

	private function create_question( QuestionGroup $question_group, AbstractQuestion $question_props ): AbstractQuestion {
		$question_id = $this->question_dao->create( $question_props );
		if ( $question_id === false ) {
			throw new Exception( 'Could not create question.' );
		}
		$question = $this->question_dao->get( $question_id );
		return $question;
	}
}
