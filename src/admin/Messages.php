<?php

namespace tuja\admin;

use tuja\data\store\CompetitionDao;
use tuja\view\FieldImages;
use tuja\data\store\MessageDao;

class Messages {

	private $competition;
	private $messages_manager;

	public function __construct() {
		$db_competition    = new CompetitionDao();
		$this->competition = $db_competition->get( $_GET['tuja_competition'] );
		if (!$this->competition) {
			print 'Could not find competition';

			return;
		}
		$this->messages_manager = new MessagesManager( $this->competition );
	}


	public function output() {
		$this->handle_post();

		$db_message = new MessageDao();
		$messages   = $db_message->get_without_group();

		$competition      = $this->competition;
		$messages_manager = $this->messages_manager;

		include( 'views/messages.php' );
	}

	private function handle_post() {
		$this->messages_manager->handle_post();
	}
}
