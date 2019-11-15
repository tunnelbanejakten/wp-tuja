<?php

namespace tuja\view;

use tuja\data\model\ValidationException;
use Exception;
use tuja\data\store\GroupDao;
use tuja\data\store\PersonDao;
use tuja\frontend\router\PersonEditorInitiator;


class EditPersonShortcode extends AbstractShortcode
{
    private $person_key;

	public function __construct( $person_key )
    {
        $this->person_key = $person_key;
    }

    public function render(): String
    {

	    if ( isset( $this->person_key ) ) {
		    $person = ( new PersonDao() )->get_by_key( $this->person_key );
		    $group  = ( new GroupDao() )->get_by_key( $person->group_id );

		    $link = PersonEditorInitiator::link( $group, $person );

		    return sprintf( 'Sidan har flyttat till <a href="%s">%s</a>.', $link, $link );
	    } else {
		    return sprintf( '<p class="tuja-message tuja-message-error">%s</p>', 'Oj, vi vet inte vem du vill ändra.' );
	    }
    }
}