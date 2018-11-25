<?php

namespace util;

use DateTime;
use Exception;
use tuja\data\model\Message;


class MessageImporter
{
    private $message_dao;
    private $people;

    const SOURCE = 'mms';

    public function __construct($message_dao, $people)
    {
        $this->message_dao = $message_dao;
        $this->people = $people;
    }

    /**
     * Try to map sender to a group. It can be a phone number, a name or an e-mail address.
     */
    private function find_group_by_sender($sender)
    {

        $matching_people = array_filter($this->people, function ($person) use ($sender) {
            return $person->phone == $sender;
        });

        return count($matching_people) == 1 ? reset($matching_people)->group_id : null;
    }

    public function import($text, $image_ids, $sender, $timestamp)
    {
        $group_id = $this->find_group_by_sender($sender);

        $message_id = sprintf('%s,%s', $sender, $timestamp->format(DateTime::ISO8601));

        if ($this->message_dao->exists(self::SOURCE, $message_id)) {
            throw new Exception(sprintf('Meddelande %s har redan importerats.', $message_id));
        }

        $message = new Message();
        $message->form_question_id = null;
        $message->group_id = $group_id;
        $message->text = $text;
        $message->image = join(',', $image_ids);
        $message->source = self::SOURCE;
        $message->source_message_id = $message_id;
        $message->date_received = $timestamp;

        $res = $this->message_dao->create($message);

        if ($res === false) {
            throw new Exception(sprintf('Sparade inte meddelande %s i databasen pga. databasfel.', $message->source_message_id));
        }

        return $message;
    }
}