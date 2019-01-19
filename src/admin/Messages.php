<?php

namespace tuja\admin;

use tuja\view\FieldImages;
use tuja\data\store\MessageDao;

class Messages {

	private $competition;

	public function __construct() {
		$this->competition = $db_competition->get($_GET['tuja_competition']);
		if (!$this->competition) {
			print 'Could not find competition';
			return;
		}
	}


	public function output() {
		$db_message = new MessageDao();
		$messages = $db_message->get_without_group();

		$competition_url = add_query_arg(array(
			'tuja_competition' => $this->competition->id,
			'tuja_view' => 'competition'
		));

		include('view/messages.php');
	}
}
