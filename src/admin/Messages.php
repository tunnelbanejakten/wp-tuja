<?php

namespace tuja\admin;

use tuja\data\store\MessageDao;

class Messages extends Competition {

	protected $messages_manager;

	public function __construct() {
		parent::__construct();
		$this->messages_manager = new MessagesManager( $this->competition );
	}

	protected function create_menu( string $current_view_name, array $parents ): BreadcrumbsMenu {
		$menu = parent::create_menu( $current_view_name, $parents );

		return $this->add_static_menu(
			$menu,
			array(
				Messages::class       => 'Översikt',
				MessagesImport::class => 'Importera meddelanden',
				MessagesSend::class   => 'Skicka meddelanden',
			)
		);
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
