<?php

namespace tuja\view;

use tuja\data\model\ValidationException;
use Exception;
use tuja\data\model\Group;
use tuja\data\model\Person;
use tuja\data\model\Question;
use tuja\data\store\GroupDao;
use tuja\frontend\router\GroupSignupInitiator;
use tuja\frontend\router\PersonEditorInitiator;
use tuja\util\messaging\EventMessageSender;
use tuja\util\messaging\MessageSender;
use tuja\util\Recaptcha;


class CreatePersonShortcode extends AbstractShortcode
{

    private $group_key;

	public function __construct( $group_key )
    {
        $this->group_key = $group_key;
    }

    public function render(): String
    {
	    $group = ( new GroupDao() )->get_by_key( $this->group_key );

	    if ( isset( $group ) ) {
		    $link = GroupSignupInitiator::link( $group );

		    return sprintf( 'Anmälan har flyttat till <a href="%s">%s</a>.', $link, $link );
        } else {
            return sprintf('<p class="tuja-message tuja-message-error">%s</p>', 'Oj, vi vet inte vilket lag du vill anmäla dig till.');
        }
    }
}