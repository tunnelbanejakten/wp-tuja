<?php

namespace tuja\admin;

use tuja\data\store\CompetitionDao;
use tuja\view\FieldImages;
use tuja\data\store\MessageDao;

class Messages extends AbstractCompetitionPage {

	private $messages_manager;

	public function __construct() {
		parent::__construct();
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
