<?php

namespace tuja\util\messaging;

use Exception;
use tuja\data\model\Person;
use tuja\util\Phone;

class OutgoingSMSMessage extends AbstractOutgoingMessage {
	private $body;

	const MOBILE_PHONE_INTL_PREFIX = '+467';

	public function __construct( MessageSender $message_sender, Person $recipient, $body ) {
		parent::__construct( $message_sender, $recipient );
		$this->body = $body;
	}

	function validate() {
		$length = strlen( $this->body );
		if ( $length > 160 ) {
			throw new Exception( sprintf( 'Meddelande %d tecken för långt.', $length - 160 ) );
		}
		$phone_number = Phone::fix_phone_number( $this->recipient->phone );
		if ( empty( $phone_number ) ) {
			throw new Exception( 'Saknar telefonnummer.' );
		}
		if ( substr( $phone_number, 0, strlen( self::MOBILE_PHONE_INTL_PREFIX ) ) !== self::MOBILE_PHONE_INTL_PREFIX ) {
			throw new Exception( sprintf( 'Telefonnummer måste börja med %s', self::MOBILE_PHONE_INTL_PREFIX ) );
		}
	}

	function send() {
		$this->validate();

		$this->message_sender->send_sms(
			Phone::fix_phone_number( $this->recipient->phone ),
			$this->body );
	}
}
