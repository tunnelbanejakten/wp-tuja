<?php

namespace tuja\data\model;

use Exception;
use tuja\util\messaging\EventMessageSender;
use tuja\util\messaging\MessageSender;
use tuja\util\messaging\OutgoingEmailMessage;
use tuja\util\messaging\OutgoingSMSMessage;
use tuja\util\Template;

class MessageTemplate {
	const SMS   = 'sms';
	const EMAIL = 'email';

	public $id;
	public $competition_id;
	public $delivery_method;
	public $name;
	public $subject;
	public $body;
	public $auto_send_trigger;
	public $auto_send_recipient;

	public static function default_templates() {
		$filenames = array_filter(
			scandir( __DIR__ . '/../../admin/default_message_template' ),
			function ( $filename ) {
				return strpos( $filename, '.ini' ) !== false;
			}
		);
		return array_reduce(
			$filenames,
			function ( $res, $filename ) {
				$strings = parse_ini_file( __DIR__ . '/../../admin/default_message_template/' . $filename );
				$mt      = new MessageTemplate();
				foreach ( $strings as $key => $value ) {
					$mt->{$key} = $value;
				}
				$mt->validate();
				$res[ basename( $filename, '.ini' ) ] = $mt;
				return $res;
			},
			array()
		);
	}

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
		if ( $this->delivery_method != null && ! in_array(
			$this->delivery_method,
			array(
				self::EMAIL,
				self::SMS,
			)
		) ) {
			throw new ValidationException( 'delivery_method', 'Ogiltig typ' );
		}
		if ( $this->auto_send_trigger != null && ! in_array( $this->auto_send_trigger, array_keys( EventMessageSender::event_names() ) ) ) {
			throw new ValidationException( 'auto_send_trigger', 'Ogiltig typ' );
		}
		if ( $this->auto_send_recipient != null && ! in_array(
			$this->auto_send_recipient,
			array(
				EventMessageSender::RECIPIENT_ADMIN,
				EventMessageSender::RECIPIENT_GROUP_CONTACT,
				EventMessageSender::RECIPIENT_SELF,
			)
		) ) {
			throw new ValidationException( 'auto_send_recipient', 'Ogiltig mottagare' );
		}
	}

	public function to_message( Person $contact, $template_parameters ) {
		switch ( $this->delivery_method ) {
			case self::SMS:
				return new OutgoingSMSMessage(
					new MessageSender(),
					$contact,
					$this->render_body( $template_parameters, true )
				);
			case self::EMAIL:
				return new OutgoingEmailMessage(
					new MessageSender(),
					$contact,
					$this->render_body( $template_parameters ),
					Template::string( $this->subject )->render( $template_parameters )
				);
		}
		throw new Exception( 'Invalid type: ' . $this->delivery_method );
	}

	public function render_body( $template_parameters, $is_plaintext = false ) {
		return Template::string( $this->body )->render( $template_parameters, ! $is_plaintext );
	}
}
