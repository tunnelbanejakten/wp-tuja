<?php

namespace tuja\admin;

use tuja\data\store\UploadDao;

class Uploads extends Competition {
	protected $upload_dao;

	public function __construct() {
		parent::__construct();

		$this->upload_dao = new UploadDao();
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		return $this->add_static_menu(
			$menu,
			array(
				UploadsList::class => array( 'Alla bilder', null ),
				UploadsSync::class => array( 'Synka databas och disk', null ),
			)
		);
	}

	public function output() {
		include( 'views/uploads.php' );
	}
}
