<?php

namespace tuja\view;

use tuja\data\model\GroupCategory;
use tuja\data\model\ValidationException;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\store\CompetitionDao;
use tuja\frontend\router\CompetitionSignupInitiator;
use tuja\frontend\router\GroupEditorInitiator;
use tuja\util\messaging\EventMessageSender;
use tuja\util\Recaptcha;
use tuja\util\rules\RuleEvaluationException;


class CreateGroupShortcode {

	private $competition_id;

	public function __construct( $competition_id ) {
		$this->competition_id = $competition_id;
	}

	public function render(): String {
		if ( isset( $this->competition_id ) ) {
			$competition = ( new CompetitionDao() )->get( $this->competition_id );

			$link = CompetitionSignupInitiator::link( $competition );

			return sprintf( 'Anmälan har flyttat till <a href="%s">%s</a>.', $link, $link );
		} else {
			return sprintf( '<p class="tuja-message tuja-message-error">%s</p>', 'Oj, vi vet inte vilken tävling du vill anmäla dig till.' );
		}
	}
}