<?php

namespace tuja\util\messaging;

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