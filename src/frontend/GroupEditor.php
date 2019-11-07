<?php

namespace tuja\frontend;


use tuja\data\model\Group;
use tuja\data\store\GroupDao;
use tuja\frontend\router\GroupHomeInitiator;

class GroupEditor extends FrontendView {
	private $group_key;
	private $group_dao;

	public function __construct( $url, $group_key ) {
		parent::__construct( $url );
		$this->group_key = $group_key;
		$this->group_dao = new GroupDao();
	}

	function render() {
		$group = $this->get_group();
		$home_link = GroupHomeInitiator::link( $group );
		include( 'views/group-editor.php' );
	}

	function get_title() {
		return $this->get_group()->name;
	}

	function get_group(): Group {
		return $this->group_dao->get_by_key($this->group_key);
	}
}