<?php

namespace tuja\util\messaging;

use Exception;
use tuja\data\model\Person;

class OutgoingEmailMessage extends AbstractOutgoingMessage {
	private $body;
	private $subject;

	public function __construct( MessageSender $message_sender, Person $recipient, $body, $subject ) {
		parent::__construct( $message_sender, $recipient );
		$this->body    = $body;
		$this->subject = $subject;
	}

	function validate() {
		if ( empty( trim( $this->recipient->email ) ) ) {
			throw new Exception( 'Saknar e-postadress.' );
		}
	}

	function send() {
		$this->validate();

		$mail_result = $this->message_sender->send_mail(
			$this->recipient->email,
			$this->subject,
			$this->body
		);

		if ( ! $mail_result ) {
			throw new Exception( 'Ett fel uppstod. Vi vet tyvärr inte riktigt varför.' );
		}
	}

	function recipient_description(): string {
		return $this->recipient->email;
	}
}
