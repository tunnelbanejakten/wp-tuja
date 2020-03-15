<?php

namespace tuja\admin;


use tuja\data\model\question\AbstractQuestion;
use tuja\data\model\QuestionGroup;
use tuja\data\store\FormDao;
use tuja\data\store\GroupDao;
use tuja\data\model\Group;
use tuja\data\store\QuestionDao;
use tuja\data\store\QuestionGroupDao;

class ReportQuestionFiltering extends AbstractReport {
	private $group_dao;
	private $form_dao;
	private $question_group_dao;
	private $question_dao;

	public function __construct() {
		parent::__construct();
		$this->group_dao          = new GroupDao();
		$this->form_dao           = new FormDao();
		$this->question_group_dao = new QuestionGroupDao();
		$this->question_dao       = new QuestionDao();
	}

	function get_rows(): array {
		$forms  = $this->form_dao->get_all_in_competition( $this->competition->id );
		$groups = $this->group_dao->get_all_in_competition( $this->competition->id );

		try {
			$res = array_map( function ( \tuja\data\model\Form $form ) use ( $groups ) {
				$question_groups = $this->question_group_dao->get_all_in_form( $form->id );

				return [
					'form_name'       => $form->name,
					'question_groups' => array_map( function ( QuestionGroup $question_group ) use ( $groups ) {
						return [
							'question_group'    => $question_group->text,
							'questions_by_team' => array_map( function ( Group $group ) use ( $question_group ) {
								$questions = $question_group->get_filtered_questions( $this->question_dao, $this->group_dao, $group );

								return [
									'team_name' => $group->name,
									'team_id'   => $group->id,
									'questions' => array_map( function ( AbstractQuestion $question ) {
										return [
											'id'   => $question->id,
											'text' => $question->text
										];
									}, $questions )
								];
							}, $groups )
						];
					}, array_filter( $question_groups, function ( QuestionGroup $question_group ) {
						return $question_group->question_filter === ( $_GET['tuja_reports_question_filter'] ?: QuestionGroup::QUESTION_FILTER_ALL );
					} ) )
				];
			}, $forms );
		} catch ( \Exception $e ) {
			var_dump( $e );
		}

		return $res;
	}

	function output_html( array $rows ) {
		$forms = $rows;
		include( 'views/report-questionfiltering.php' );
	}
}