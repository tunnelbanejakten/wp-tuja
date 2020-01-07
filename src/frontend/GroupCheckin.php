<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\model\MessageTemplate;
use tuja\data\model\Person;
use tuja\data\model\ValidationException;
use tuja\data\store\MessageTemplateDao;
use tuja\frontend\router\GroupEditorInitiator;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\frontend\router\GroupPeopleEditorInitiator;
use tuja\util\messaging\EventMessageSender;
use tuja\util\rules\RuleEvaluationException;
use tuja\util\rules\RuleResult;
use tuja\util\Strings;
use tuja\util\Template;
use tuja\view\FieldChoices;
use tuja\view\FieldEmail;
use tuja\view\FieldPhone;
use tuja\view\FieldPno;
use tuja\view\FieldText;

class GroupCheckin extends AbstractGroupView {
	const FIELD_ANSWER = 'tuja-checkin-answer';

	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Incheckning för %s' );
	}

	function output() {
		$group    = $this->get_group();
		$category = $group->get_derived_group_category();

		if ( $group->get_status() == Group::STATUS_CHECKEDIN ) {
			printf( '<div class="tuja-message tuja-message-success">%s</div>', Strings::get( 'checkin.already_checked_in' ) );

			return;
		}

		if ( $group->get_status() != Group::STATUS_AWAITING_CHECKIN ) {
			throw new Exception( Strings::get( 'checkin.not_open' ) );
		}

		if ( @$_POST[ self::ACTION_BUTTON_NAME ] == self::ACTION_NAME_SAVE ) {
			$template_parameters = array_merge(
				Template::site_parameters(),
				Template::group_parameters( $group )
			);

			if ( @$_POST[ self::FIELD_ANSWER ] == Strings::get( 'checkin.yes.label' ) ) {
				$group->set_status( Group::STATUS_CHECKEDIN );
				if ( $this->group_dao->update( $group ) ) {
					printf( '<div class="tuja-message tuja-message-success">%s</div>', Strings::get( 'checkin.yes.title' ) );
					print Template::string( Strings::get( 'checkin.yes.body_text' ) )->render( $template_parameters, true );

					return;
				} else {
					throw new Exception( 'Could not set status to Checked In.' );
				}
			} else {
				printf( '<div class="tuja-message tuja-message-info">%s</div>', Strings::get( 'checkin.no.title' ) );
				print Template::string( Strings::get( 'checkin.no.body_text' ) )->render( $template_parameters, true );

				return;
			}
		}

		$people            = $this->person_dao->get_all_in_group( $group->id );
		$competing         = array_filter( $people, function ( Person $person ) {
			return $person->is_competing();
		} );
		$adult_supervisors = array_filter( $people, function ( Person $person ) {
			return $person->is_adult_supervisor();
		} );

		$form = $this->get_form_html();

		$submit_button = $this->get_submit_button_html();

		$home_link = GroupHomeInitiator::link( $group );
		include( 'views/group-checkin.php' );
	}

	private function get_submit_button_html() {
		return sprintf( '<div class="tuja-buttons"><button type="submit" name="%s" value="%s">%s</button></div>', self::ACTION_BUTTON_NAME, self::ACTION_NAME_SAVE, Strings::get( 'checkin.button.label' ) );
	}

	private function get_form_html( $errors = array() ): string {
		$html_sections = [];

		$group_category_question = new FieldChoices(
			null,
			null,
			false,
			[
				Strings::get( 'checkin.yes.label' ),
				Strings::get( 'checkin.no.label' )
			],
			false );
		$html_sections[]         = $this->render_field( $group_category_question, self::FIELD_ANSWER, $errors[ self::FIELD_ANSWER ] );

		return join( $html_sections );
	}
}