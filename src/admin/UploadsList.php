<?php

namespace tuja\admin;

use tuja\util\ImageManager;

class UploadsList extends Uploads {
	public function __construct() {
		parent::__construct();
	}

	public function output() {
		$uploads = $this->upload_dao->get_all_in_competition( $this->competition->id );

		$update_favourite_endpoint = add_query_arg(
			array(
				'action'           => 'tuja_favourite_upload',
				'tuja_upload_id'   => 'UPLOADID',
				'tuja_competition' => $this->competition->id,
			),
			admin_url( 'admin.php' )
		);

		$image_manager = new ImageManager();

		include( 'views/uploads-list.php' );
	}
}
