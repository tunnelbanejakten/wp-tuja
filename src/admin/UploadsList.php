<?php

namespace tuja\admin;

use tuja\util\ImageManager;

class UploadsList extends Uploads {
	public function __construct() {
		parent::__construct();
	}

	public function get_scripts(): array {
		return array(
			'admin-uploads.js',
		);
	}

	public function output() {
		$uploads = $this->upload_dao->get_all_in_competition( $this->competition->id );

		$image_manager = new ImageManager();

		include( 'views/uploads-list.php' );
	}
}
