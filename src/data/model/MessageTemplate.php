<?php

namespace tuja\data\model;


use Exception;
use tuja\util\messaging\MessageSender;
use tuja\util\messaging\OutgoingEmailMessage;
use tuja\util\messaging\OutgoingSMSMessage;
use tuja\util\Template;

class MessageTemplate
{
	const SMS = 'sms';
	const EMAIL = 'email';

	public $id;
	public $competition_id;
	public $delivery_method;
	public $name;
	public $subject;
	public $body;
	public $auto_send_trigger;
	public $auto_send_recipient;

	public function validate() {
		if ( strlen( $this->name ) > 100 ) {
			throw new ValidationException( 'name', 'Namnet för långt.' );
		}
		if ( strlen( $this->subject ) > 65000 ) {
			throw new ValidationException( 'subject', 'Ämnesraden för lång.' );
		}
		if ( strlen( $this->body ) > 65000 ) {
			throw new ValidationException( 'body', 'Meddelandet för långt.' );
		}
		if ( $this->delivery_method != null && in_array( $this->delivery_method, [
				self::EMAIL,
				self::SMS
			] ) ) {
			throw new ValidationException( 'delivery_method', 'Ogiltig typ' );
		}
	}

	public function to_message( Person $contact, $template_parameters ) {
		switch ( $this->delivery_method ) {
			case self::SMS:
				return new OutgoingSMSMessage(
					new MessageSender(),
					$contact,
					Template::string( $this->body )->render( $template_parameters, false ) );
			case self::EMAIL:
				return new OutgoingEmailMessage(
					new MessageSender(),
					$contact,
					Template::string( $this->body )->render( $template_parameters, true ),
					Template::string( $this->subject )->render( $template_parameters ) );
		}
		throw new Exception( 'Invalid type: ' . $this->delivery_method );
	}
}