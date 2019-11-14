<?php

namespace tuja\view;

use tuja\data\model\Group;
use tuja\data\model\ValidationException;
use Exception;
use tuja\data\model\Person;
use tuja\data\store\GroupDao;
use tuja\frontend\router\GroupHomeInitiator;
use tuja\util\rules\RuleEvaluationException;


class EditGroupShortcode
{

	private $group_key;

	public function __construct( $group_key ) {
		$this->group_key = $group_key;
	}

	public function render(): String {
		if ( isset( $this->group_key ) ) {
			$group = ( new GroupDao() )->get_by_key( $this->group_key );

			$link = GroupHomeInitiator::link( $group );

			return sprintf( 'Sidan har flyttat till <a href="%s">%s</a>.', $link, $link );
		} else {
			return sprintf( '<p class="tuja-message tuja-message-error">%s</p>', 'Oj, vi vet inte vilket lag du är med i.' );
		}
	}
}