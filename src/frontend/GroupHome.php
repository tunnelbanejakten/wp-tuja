<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\store\GroupDao;
use tuja\frontend\router\GroupEditorInitiator;
use tuja\frontend\router\GroupPeopleEditorInitiator;
use tuja\frontend\router\GroupTicketsInitiator;

class GroupHome extends AbstractGroupView {
	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, 'Hej %s' );
	}

	function output() {
		try {
			$group            = $this->get_group();

			$this->check_group_status( $group );

			$edit_group_link  = GroupEditorInitiator::link( $group );
			$edit_people_link = GroupPeopleEditorInitiator::link( $group );
			$tickets_link     = GroupTicketsInitiator::link( $group );
			include( 'views/group-home.php' );
		} catch ( Exception $e ) {
			print $this->get_exception_message_html( $e );
		}
	}
}