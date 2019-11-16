<?php

namespace tuja\frontend;


use Exception;
use tuja\data\model\Group;
use tuja\data\store\GroupDao;
use tuja\frontend\router\GroupEditorInitiator;
use tuja\frontend\router\GroupPeopleEditorInitiator;

class GroupHome extends AbstractGroupView {
	public function __construct( $url, $group_key ) {
		parent::__construct( $url, $group_key, '%s' );
	}

	function output() {
		try {
			$group            = $this->get_group();
			$edit_group_link  = GroupEditorInitiator::link( $group );
			$edit_people_link = GroupPeopleEditorInitiator::link( $group );
			include( 'views/group-home.php' );
		} catch ( Exception $e ) {
			printf( '<p class="tuja-message tuja-message-error">%s</p>', $e->getMessage() );
		}
	}
}