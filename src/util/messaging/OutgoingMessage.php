<?php

namespace tuja\util\messaging;

use Exception;
use tuja\data\model\Person;

abstract class AbstractOutgoingMessage
{
    protected $message_sender;
    protected $recipient;

    public function __construct(MessageSender $message_sender, Person $recipient)
    {
        $this->message_sender = $message_sender;
        $this->recipient = $recipient;
    }

    abstract function validate();

    abstract function send();
}

class OutgoingSMSMessage extends AbstractOutgoingMessage
{
    private $body;

    const MOBILE_PHONE_INTL_PREFIX = '+467';

    public function __construct(MessageSender $message_sender, Person $recipient, $body)
    {
        parent::__construct($message_sender, $recipient);
        $this->body = $body;
    }

    function validate()
    {
        $length = strlen($this->body);
        if ($length > 160) {
            throw new Exception(sprintf('Meddelande %d tecken för långt.', $length - 160));
        }
        $phone_number = Phone::fix_phone_number($this->recipient->phone);
        if (empty($phone_number)) {
            throw new Exception('Saknar telefonnummer.');
        }
        if (substr($phone_number, 0, strlen(self::MOBILE_PHONE_INTL_PREFIX)) == self::MOBILE_PHONE_INTL_PREFIX) {
            throw new Exception(sprintf('Telefonnummer måste börja med %s', self::MOBILE_PHONE_INTL_PREFIX));
        }
    }

    function send()
    {
        $this->validate();

        throw new Exception('SMS-stöd saknas.');
    }
}

class OutgoingEmailMessage extends AbstractOutgoingMessage
{
    private $body;
    private $subject;

    public function __construct(MessageSender $message_sender, Person $recipient, $body, $subject)
    {
        parent::__construct($message_sender, $recipient);
        $this->body = $body;
        $this->subject = $subject;
    }

    function validate()
    {
        if (empty(trim($this->recipient->email))) {
            throw new Exception('Saknar e-postadress.');
        }
    }

    function send()
    {
        $this->validate();

        $mail_result = $this->message_sender->send_mail(
            $this->recipient->email,
            $this->subject,
            $this->body);

        if (!$mail_result) {
            throw new Exception('Ett fel uppstod. Vi vet tyvärr inte riktigt varför.');
        }
    }
}